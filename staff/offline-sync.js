/**
 * PWSTAFF Offline Sync Library
 * Stores pending uploads in IndexedDB when offline.
 * Auto-syncs one-by-one when back online.
 * Also handles offline data download & page prefetching.
 */
var OfflineSync = (function() {
  var DB_NAME = 'pwstaff_sync';
  var DB_VERSION = 2;
  var STORE_NAME = 'pending_actions';
  var DATA_STORE = 'offline_data';
  var EXPECTED_CACHE = 'pwstaff-v9';
  var db = null;
  var syncing = false;
  var lastSyncError = null;

  // Open IndexedDB - resets stale connections automatically
  function openDB() {
    return new Promise(function(resolve, reject) {
      // Validate existing connection is still usable
      if (db) {
        try {
          // Test if connection is still alive by checking objectStoreNames
          // A closed connection will throw InvalidStateError
          var names = db.objectStoreNames;
          if (names.contains(STORE_NAME)) {
            resolve(db);
            return;
          }
        } catch(e) {
          // Connection is stale/closed - reset and reopen
          console.warn('IndexedDB connection stale, reopening...', e.message);
          db = null;
        }
      }
      var request = indexedDB.open(DB_NAME, DB_VERSION);
      request.onupgradeneeded = function(e) {
        var d = e.target.result;
        if (!d.objectStoreNames.contains(STORE_NAME)) {
          var store = d.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
          store.createIndex('status', 'status', { unique: false });
          store.createIndex('created_at', 'created_at', { unique: false });
        }
        if (!d.objectStoreNames.contains(DATA_STORE)) {
          d.createObjectStore(DATA_STORE, { keyPath: 'key' });
        }
      };
      request.onsuccess = function(e) {
        db = e.target.result;
        // Listen for unexpected close (mobile browser kills connection in background)
        db.onclose = function() {
          console.warn('IndexedDB connection closed unexpectedly');
          db = null;
        };
        db.onversionchange = function() {
          db.close();
          db = null;
        };
        resolve(db);
      };
      request.onerror = function(e) { reject(new Error('IndexedDB open failed: ' + (e.target.error ? e.target.error.message : 'unknown'))); };
    });
  }

  // ==================== OFFLINE DATA STORE ====================

  // Save data to offline store (with quota error handling)
  function saveData(key, data) {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(DATA_STORE, 'readwrite');
        var store = tx.objectStore(DATA_STORE);
        var req = store.put({ key: key, data: data, saved_at: new Date().toISOString() });
        req.onsuccess = function() { resolve(); };
        req.onerror = function() {
          var err = req.error;
          if (err && err.name === 'QuotaExceededError') {
            reject(new Error('Storage full. Please clear old data or free up device storage.'));
          } else {
            reject(err);
          }
        };
        // Handle transaction errors (connection may have dropped)
        tx.onerror = function() { reject(new Error('DB transaction failed: ' + (tx.error ? tx.error.message : 'unknown'))); };
        tx.onabort = function() {
          db = null; // Reset connection on abort
          reject(new Error('DB transaction aborted - will retry on next attempt'));
        };
      });
    });
  }

  // Get data from offline store
  function getData(key) {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(DATA_STORE, 'readonly');
        var store = tx.objectStore(DATA_STORE);
        var req = store.get(key);
        req.onsuccess = function() { resolve(req.result || null); };
        req.onerror = function() { reject(req.error); };
        tx.onerror = function() { reject(new Error('DB read failed')); };
        tx.onabort = function() { db = null; reject(new Error('DB read aborted')); };
      });
    });
  }

  // ==================== PAGE PREFETCH ====================

  // Get the correct SW cache name - MUST match what the service worker uses
  function getCacheName() {
    return caches.keys().then(function(names) {
      // Look for the exact expected cache first
      for (var i = 0; i < names.length; i++) {
        if (names[i] === EXPECTED_CACHE) {
          return EXPECTED_CACHE;
        }
      }
      // Then look for any pwstaff-v* cache
      for (var i = 0; i < names.length; i++) {
        if (names[i].indexOf('pwstaff-v') === 0) {
          return names[i];
        }
      }
      // Fallback: use the expected cache name (SW will create it)
      // IMPORTANT: Must match SW's CACHE_NAME to avoid being deleted on activate
      return EXPECTED_CACHE;
    });
  }

  // Prefetch pages for offline use - writes directly to Cache API
  function prefetchPages(pages, onProgress) {
    var completed = 0;
    var total = pages.length;
    var results = { success: 0, failed: 0, failedUrls: [] };

    function fetchOne(url, cache) {
      // X-Prefetch header tells the SW to NOT intercept this request
      // so the fetch goes directly to the network and we cache the response ourselves
      return fetch(url, {
        credentials: 'same-origin',
        headers: { 'X-Prefetch': 'true' }
      }).then(function(response) {
        completed++;
        if (response.ok && !response.redirected) {
          // Validate content type - only cache HTML/JSON/JS/CSS responses, not error pages
          var ct = response.headers.get('content-type') || '';
          if (ct.indexOf('text/html') !== -1 || ct.indexOf('application/json') !== -1 || ct.indexOf('text/css') !== -1 || ct.indexOf('javascript') !== -1) {
            results.success++;
            // Build full URL for consistent cache key matching
            var fullUrl = new URL(url, location.origin).href;
            // Cache by full URL string - must match how SW looks up pages
            return cache.put(fullUrl, response);
          } else {
            results.failed++;
            results.failedUrls.push(url + ' (bad content-type: ' + ct + ')');
          }
        } else {
          results.failed++;
          results.failedUrls.push(url + ' (status: ' + response.status + (response.redirected ? ', redirected' : '') + ')');
        }
      }).catch(function(err) {
        completed++;
        results.failed++;
        results.failedUrls.push(url + ' (' + (err.message || 'network error') + ')');
      });
    }

    return getCacheName().then(function(name) {
      return caches.open(name);
    }).then(function(cache) {
      var queue = pages.slice();
      function runNext() {
        if (queue.length === 0) return Promise.resolve();
        var url = queue.shift();
        return fetchOne(url, cache).then(function() {
          if (onProgress) onProgress(completed, total, results);
          return runNext();
        });
      }

      // Start 2 parallel chains
      return Promise.all([runNext(), runNext()]).then(function() {
        return results;
      });
    });
  }

  // Download all delivery data for offline use
  function downloadOfflineData(onProgress) {
    if (onProgress) onProgress('downloading', 'Downloading delivery data...');
    return fetch('offline_download.php', { credentials: 'same-origin' })
      .then(function(r) {
        if (!r.ok) throw new Error('Server returned ' + r.status + ' ' + r.statusText);
        var ct = r.headers.get('content-type') || '';
        if (ct.indexOf('application/json') === -1) {
          throw new Error('Server returned non-JSON response (session may have expired). Please refresh and try again.');
        }
        return r.json();
      })
      .then(function(data) {
        if (data.error) throw new Error(data.error);
        return saveData('delivery_data', data).then(function() {
          if (onProgress) onProgress('done', 'Data downloaded');
          return data;
        });
      });
  }

  // Full offline download: data + page prefetch
  function downloadAll(userType, onProgress) {
    var pageResults = null;
    var deliveryData = null;

    // ALL PHP files in the staff directory (excluding logout which kills session)
    var pages = [
      '/staff/index.php',
      '/staff/account.php',
      '/staff/category.php',
      '/staff/products.php',
      '/staff/all_products.php',
      '/staff/all_products_ajax.php',
      '/staff/cart.php',
      '/staff/confirm.php',
      '/staff/submit_order.php',
      '/staff/icon.php',
      '/staff/del_dashboard.php',
      '/staff/del_dashboard_ajax.php',
      '/staff/del_work.php',
      '/staff/del_work_ajax.php',
      '/staff/del_vieworder.php',
      '/staff/del_sign.php',
      '/staff/del_sign_ajax.php',
      '/staff/del_history.php',
      '/staff/del_report.php',
      '/staff/del_report_ajax.php',
      '/staff/staff_stock_take.php',
      '/staff/staff_stock_take_ajax.php',
      '/staff/staff_stock_loss.php',
      '/staff/staff_stock_loss_ajax.php',
      '/staff/product_rack_ajax.php',
      '/staff/product_search_ajax.php',
      '/staff/navbar.php',
      '/staff/mobile-bottombar.php',
      '/staff/offline_download.php',
    ];

    if (onProgress) onProgress('start', 0, pages.length + 1, 'Starting offline download...');

    var completedSteps = 0;
    var totalSteps = pages.length + 1; // +1 for data download

    // Step 1: Download data
    return downloadOfflineData(function() {})
      .then(function(data) {
        deliveryData = data;
        completedSteps++;
        if (onProgress) onProgress('progress', completedSteps, totalSteps, 'Data downloaded. Caching pages...');

        // Also prefetch individual order pages (del_work with specific IDs, del_vieworder with specific ordno)
        if (data && data.orders) {
          for (var i = 0; i < data.orders.length; i++) {
            var orderId = data.orders[i].ID;
            var ordno = data.orders[i].ORDNO;
            pages.push('/staff/del_work.php?id=' + orderId);
            pages.push('/staff/del_vieworder.php?ordno=' + encodeURIComponent(ordno));
            pages.push('/staff/del_sign.php?ordno=' + encodeURIComponent(ordno) + '&id=' + orderId);
          }
          totalSteps = pages.length + 1;
        }

        // Step 2: Prefetch all pages
        return prefetchPages(pages, function(done, total, results) {
          completedSteps = 1 + done;
          if (onProgress) onProgress('progress', completedSteps, totalSteps, 'Cached ' + done + ' of ' + total + ' pages...');
        });
      })
      .then(function(results) {
        pageResults = results;
        if (onProgress) onProgress('complete', completedSteps, totalSteps, 'Download complete!');
        return {
          data: deliveryData,
          pages: pageResults,
          totalPages: pageResults ? pageResults.success : 0,
          failedPages: pageResults ? pageResults.failed : 0,
          failedUrls: pageResults ? pageResults.failedUrls : [],
          totalSteps: totalSteps
        };
      })
      .catch(function(err) {
        if (onProgress) onProgress('error', completedSteps, totalSteps, 'Error: ' + err.message);
        throw err;
      });
  }

  // ==================== PENDING ACTIONS ====================

  // Check for duplicate pending action (same type + same payload URL + same ID)
  function hasDuplicate(type, payload) {
    return getPending().then(function(records) {
      for (var i = 0; i < records.length; i++) {
        var r = records[i];
        if (r.type !== type) continue;
        var rp = r.payload;
        // Match on URL + action + id (covers job_done, photo_upload, signature, etc.)
        if (rp.url === payload.url) {
          if (rp.fields && payload.fields && rp.fields.action === payload.fields.action && rp.fields.id === payload.fields.id) return true;
          if (rp.body && payload.body && rp.body === payload.body) return true;
        }
      }
      return false;
    });
  }

  // Add a pending action to IndexedDB (with deduplication)
  function addPending(type, description, payload) {
    return hasDuplicate(type, payload).then(function(isDup) {
      if (isDup) {
        // Already queued - return existing, don't add duplicate
        return -1;
      }
      return openDB().then(function(d) {
        return new Promise(function(resolve, reject) {
          var tx = d.transaction(STORE_NAME, 'readwrite');
          var store = tx.objectStore(STORE_NAME);
          var record = {
            type: type,
            description: description,
            payload: payload,
            status: 'pending',
            created_at: new Date().toISOString(),
            synced_at: null,
            error: null,
            retries: 0
          };
          var req = store.add(record);
          req.onsuccess = function() { resolve(req.result); };
          req.onerror = function() { reject(req.error); };
          tx.onerror = function() { reject(new Error('DB write failed')); };
          tx.onabort = function() { db = null; reject(new Error('DB write aborted')); };
        });
      });
    });
  }

  // Get all records (for display)
  function getAll() {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(STORE_NAME, 'readonly');
        var store = tx.objectStore(STORE_NAME);
        var req = store.getAll();
        req.onsuccess = function() { resolve(req.result || []); };
        req.onerror = function() { reject(req.error); };
        tx.onerror = function() { reject(new Error('DB read failed')); };
        tx.onabort = function() { db = null; reject(new Error('DB read aborted')); };
      });
    });
  }

  // Get pending records only
  function getPending() {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(STORE_NAME, 'readonly');
        var store = tx.objectStore(STORE_NAME);
        var idx = store.index('status');
        var req = idx.getAll('pending');
        req.onsuccess = function() { resolve(req.result || []); };
        req.onerror = function() { reject(req.error); };
        tx.onerror = function() { reject(new Error('DB query failed')); };
        tx.onabort = function() { db = null; reject(new Error('DB query aborted')); };
      });
    });
  }

  // Get failed records
  function getFailed() {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(STORE_NAME, 'readonly');
        var store = tx.objectStore(STORE_NAME);
        var idx = store.index('status');
        var req = idx.getAll('failed');
        req.onsuccess = function() { resolve(req.result || []); };
        req.onerror = function() { reject(req.error); };
      });
    });
  }

  // Update record status
  function updateRecord(id, updates) {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(STORE_NAME, 'readwrite');
        var store = tx.objectStore(STORE_NAME);
        var getReq = store.get(id);
        getReq.onsuccess = function() {
          var record = getReq.result;
          if (!record) { reject(new Error('Record #' + id + ' not found in DB')); return; }
          for (var k in updates) { record[k] = updates[k]; }
          var putReq = store.put(record);
          putReq.onsuccess = function() { resolve(record); };
          putReq.onerror = function() { reject(new Error('DB update failed for record #' + id)); };
        };
        getReq.onerror = function() { reject(new Error('DB get failed for record #' + id)); };
        tx.onerror = function() { reject(new Error('DB transaction error updating #' + id)); };
        tx.onabort = function() { db = null; reject(new Error('DB transaction aborted updating #' + id)); };
      });
    });
  }

  // Delete synced records older than 7 days
  function cleanOld() {
    return openDB().then(function(d) {
      return new Promise(function(resolve) {
        var tx = d.transaction(STORE_NAME, 'readwrite');
        var store = tx.objectStore(STORE_NAME);
        var cutoff = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString();
        var req = store.openCursor();
        req.onsuccess = function(e) {
          var cursor = e.target.result;
          if (cursor) {
            var r = cursor.value;
            if (r.status === 'synced' && r.synced_at && r.synced_at < cutoff) {
              cursor.delete();
            }
            cursor.continue();
          } else {
            resolve();
          }
        };
        req.onerror = function() { resolve(); };
      });
    });
  }

  // Convert base64 to Blob
  function base64ToBlob(base64, mimeType) {
    var byteString = atob(base64.split(',')[1] || base64);
    var ab = new ArrayBuffer(byteString.length);
    var ia = new Uint8Array(ab);
    for (var i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], { type: mimeType || 'image/jpeg' });
  }

  // Check if the server is truly reachable (not just navigator.onLine)
  function checkConnectivity() {
    if (!navigator.onLine) return Promise.resolve(false);
    return fetch('offline_download.php', {
      method: 'HEAD',
      credentials: 'same-origin',
      cache: 'no-store'
    }).then(function(r) {
      return r.ok || r.status === 401 || r.status === 403;
    }).catch(function() {
      return false;
    });
  }

  // Parse server response safely - handles non-JSON and session expiry
  function parseResponse(response) {
    if (!response.ok) {
      return Promise.reject(new Error('Server error: HTTP ' + response.status + ' ' + response.statusText));
    }
    var ct = response.headers.get('content-type') || '';
    if (ct.indexOf('application/json') === -1 && ct.indexOf('text/html') !== -1) {
      // Server returned HTML - likely session expired and redirected to login page
      return Promise.reject(new Error('Session expired - please open the app and log in again, then sync will resume'));
    }
    return response.text().then(function(text) {
      try {
        return JSON.parse(text);
      } catch(e) {
        // Response was not valid JSON
        var preview = text.substring(0, 100);
        if (preview.indexOf('login') !== -1 || preview.indexOf('Login') !== -1) {
          return Promise.reject(new Error('Session expired - please log in again and retry sync'));
        }
        return Promise.reject(new Error('Server returned invalid response: ' + preview));
      }
    });
  }

  // Sync a single record
  function syncOne(record) {
    var payload = record.payload;

    // Add body string if present (for signature)
    if (payload.body) {
      return fetch(payload.url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.body
      }).then(parseResponse);
    }

    var formData = new FormData();

    // Add fields
    if (payload.fields) {
      for (var key in payload.fields) {
        formData.append(key, payload.fields[key]);
      }
    }

    // Add files - send as base64 POST fields (more reliable than Blob for offline sync)
    // PHP's $_FILES mechanism can silently fail due to upload_max_filesize, post_max_size,
    // temp directory issues, or Blob handling differences across browsers.
    // Sending base64 as POST fields bypasses all of these issues.
    if (payload.files && payload.files.length > 0) {
      for (var i = 0; i < payload.files.length; i++) {
        var f = payload.files[i];
        // Send the raw base64 data URL string as a POST field with _base64 suffix
        // Server accepts both $_FILES[key] and $_POST[key_base64]
        formData.append(f.key + '_base64', f.data);
      }
    }

    return fetch(payload.url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(parseResponse);
  }

  // Cross-tab lock using localStorage to prevent duplicate syncs
  var SYNC_LOCK_KEY = 'pwstaff_sync_lock';
  var SYNC_LOCK_TTL = 60000; // 60s max lock hold time

  function acquireSyncLock() {
    try {
      var lock = localStorage.getItem(SYNC_LOCK_KEY);
      if (lock) {
        var lockTime = parseInt(lock, 10);
        if (Date.now() - lockTime < SYNC_LOCK_TTL) return false; // another tab holds lock
      }
      localStorage.setItem(SYNC_LOCK_KEY, String(Date.now()));
      return true;
    } catch(e) { return true; } // if localStorage unavailable, proceed anyway
  }

  function releaseSyncLock() {
    try { localStorage.removeItem(SYNC_LOCK_KEY); } catch(e) {}
  }

  // Sync all pending records one by one
  function syncAll() {
    if (syncing) return Promise.resolve();
    if (!navigator.onLine) return Promise.resolve();
    if (!acquireSyncLock()) return Promise.resolve();

    syncing = true;
    lastSyncError = null;
    updateSyncUI('syncing');

    return checkConnectivity().then(function(isReachable) {
      if (!isReachable) {
        syncing = false;
        releaseSyncLock();
        lastSyncError = 'Server not reachable. Check your internet connection.';
        updateSyncUI('error');
        return;
      }

      return getPending().then(function(records) {
        if (records.length === 0) {
          syncing = false;
          releaseSyncLock();
          updateSyncUI('idle');
          return;
        }

        // Sort: photo_upload and install_upload first, then signature, then job_done last
        // This ensures photos are uploaded before job_done checks for them
        var typeOrder = { photo_upload: 0, install_upload: 1, signature: 2, job_done: 3 };
        records.sort(function(a, b) {
          var orderA = typeOrder[a.type] !== undefined ? typeOrder[a.type] : 2;
          var orderB = typeOrder[b.type] !== undefined ? typeOrder[b.type] : 2;
          if (orderA !== orderB) return orderA - orderB;
          return a.id - b.id; // Within same type, process oldest first
        });

        var chain = Promise.resolve();
        var sessionExpired = false;

        records.forEach(function(record) {
          chain = chain.then(function() {
            // If session expired on a previous record, stop trying
            if (sessionExpired) return Promise.resolve();

            return syncOne(record).then(function(response) {
              if (response && response.success) {
                return updateRecord(record.id, {
                  status: 'synced',
                  synced_at: new Date().toISOString(),
                  error: null
                });
              } else {
                var errorMsg = (response && response.error) || 'Server returned no success status';
                // Re-read the current retries from DB to avoid stale count
                return openDB().then(function(d) {
                  return new Promise(function(resolve, reject) {
                    var tx = d.transaction(STORE_NAME, 'readonly');
                    var req = tx.objectStore(STORE_NAME).get(record.id);
                    req.onsuccess = function() { resolve(req.result); };
                    req.onerror = function() { resolve(record); }; // fallback to in-memory
                  });
                }).then(function(freshRecord) {
                  var retries = ((freshRecord && freshRecord.retries) || 0) + 1;
                  var updates = {
                    error: errorMsg,
                    retries: retries
                  };
                  if (retries >= 3) updates.status = 'failed';
                  return updateRecord(record.id, updates);
                });
              }
            }).catch(function(err) {
              var errorMsg = err.message || 'Network error';
              // Detect session expiry - stop trying other records too
              if (errorMsg.indexOf('Session expired') !== -1 || errorMsg.indexOf('session expired') !== -1) {
                sessionExpired = true;
                lastSyncError = errorMsg;
                // Don't increment retries for session errors - it's not the record's fault
                return updateRecord(record.id, {
                  error: errorMsg
                });
              }
              return openDB().then(function(d) {
                return new Promise(function(resolve, reject) {
                  var tx = d.transaction(STORE_NAME, 'readonly');
                  var req = tx.objectStore(STORE_NAME).get(record.id);
                  req.onsuccess = function() { resolve(req.result); };
                  req.onerror = function() { resolve(record); };
                });
              }).then(function(freshRecord) {
                var retries = ((freshRecord && freshRecord.retries) || 0) + 1;
                var updates = {
                  error: errorMsg,
                  retries: retries
                };
                if (retries >= 3) updates.status = 'failed';
                return updateRecord(record.id, updates);
              });
            }).then(function() {
              updateSyncUI('syncing');
            });
          });
        });

        return chain.then(function() {
          syncing = false;
          releaseSyncLock();
          if (sessionExpired) {
            updateSyncUI('error');
          } else {
            updateSyncUI('done');
          }
          cleanOld();
        });
      });
    }).catch(function(err) {
      syncing = false;
      releaseSyncLock();
      lastSyncError = 'Sync error: ' + (err.message || 'Unknown error');
      console.error('OfflineSync.syncAll failed:', err);
      // Reset stale DB connection if that was the cause
      if (err.message && (err.message.indexOf('DB') !== -1 || err.message.indexOf('IndexedDB') !== -1 || err.message.indexOf('aborted') !== -1)) {
        db = null;
      }
      updateSyncUI('error');
    });
  }

  // Retry all failed records - resets them to pending so syncAll can pick them up
  function retryFailed() {
    return getFailed().then(function(records) {
      if (records.length === 0) return 0;
      var chain = Promise.resolve();
      var count = records.length;
      records.forEach(function(record) {
        chain = chain.then(function() {
          return updateRecord(record.id, {
            status: 'pending',
            retries: 0,
            error: null
          });
        });
      });
      return chain.then(function() {
        updateSyncUI('idle');
        return count;
      });
    });
  }

  // Delete a specific failed record
  function deleteRecord(id) {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(STORE_NAME, 'readwrite');
        var store = tx.objectStore(STORE_NAME);
        var req = store.delete(id);
        req.onsuccess = function() { resolve(); };
        req.onerror = function() { reject(req.error); };
      });
    });
  }

  // Convert File to base64 for IndexedDB storage
  function fileToBase64(file) {
    return new Promise(function(resolve, reject) {
      var reader = new FileReader();
      reader.onload = function() { resolve(reader.result); };
      reader.onerror = function() { reject(reader.error); };
      reader.readAsDataURL(file);
    });
  }

  // UI update callback
  var uiCallback = null;
  function onSyncUpdate(cb) { uiCallback = cb; }
  function updateSyncUI(state) {
    if (uiCallback) {
      getAll().then(function(all) {
        var pending = all.filter(function(r) { return r.status === 'pending'; });
        var failed = all.filter(function(r) { return r.status === 'failed'; });
        uiCallback(state, pending.length, all, lastSyncError, failed.length);
      }).catch(function(err) {
        // If even getAll fails, still notify UI about the error
        console.error('updateSyncUI failed:', err);
        uiCallback(state, 0, [], 'Database error: ' + err.message, 0);
      });
    }
  }

  // Check if online and auto-sync
  function init() {
    openDB().then(function() {
      if (navigator.onLine) {
        setTimeout(function() { syncAll(); }, 2000);
      }
    }).catch(function(err) {
      console.error('OfflineSync init DB failed:', err);
    });

    window.addEventListener('online', function() {
      // Small delay to let the network stabilize
      setTimeout(function() { syncAll(); }, 1000);
    });

    // Periodic check every 30s when online
    setInterval(function() {
      if (navigator.onLine && !syncing) {
        getPending().then(function(records) {
          if (records.length > 0) syncAll();
        }).catch(function(err) {
          // DB connection may be stale - reset it
          console.warn('Periodic sync check failed, resetting DB connection:', err.message);
          db = null;
        });
      }
    }, 30000);
  }

  return {
    init: init,
    addPending: addPending,
    getAll: getAll,
    getPending: getPending,
    getFailed: getFailed,
    syncAll: syncAll,
    retryFailed: retryFailed,
    deleteRecord: deleteRecord,
    fileToBase64: fileToBase64,
    onSyncUpdate: onSyncUpdate,
    cleanOld: cleanOld,
    getLastError: function() { return lastSyncError; },
    // Offline data methods
    saveData: saveData,
    getData: getData,
    prefetchPages: prefetchPages,
    downloadOfflineData: downloadOfflineData,
    downloadAll: downloadAll
  };
})();

// Initialize on load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { OfflineSync.init(); });
} else {
  OfflineSync.init();
}
