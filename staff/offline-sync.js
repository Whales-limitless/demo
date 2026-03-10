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
  var db = null;
  var syncing = false;

  // Open IndexedDB
  function openDB() {
    return new Promise(function(resolve, reject) {
      if (db) { resolve(db); return; }
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
        resolve(db);
      };
      request.onerror = function(e) { reject(e.target.error); };
    });
  }

  // ==================== OFFLINE DATA STORE ====================

  // Save data to offline store
  function saveData(key, data) {
    return openDB().then(function(d) {
      return new Promise(function(resolve, reject) {
        var tx = d.transaction(DATA_STORE, 'readwrite');
        var store = tx.objectStore(DATA_STORE);
        var req = store.put({ key: key, data: data, saved_at: new Date().toISOString() });
        req.onsuccess = function() { resolve(); };
        req.onerror = function() { reject(req.error); };
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
      });
    });
  }

  // ==================== PAGE PREFETCH ====================

  // Prefetch pages for offline use - writes directly to Cache API
  function prefetchPages(pages, onProgress) {
    var completed = 0;
    var total = pages.length;
    var results = { success: 0, failed: 0 };

    // Find the active pwstaff cache
    function getCacheName() {
      return caches.keys().then(function(names) {
        for (var i = 0; i < names.length; i++) {
          if (names[i].indexOf('pwstaff-') === 0) {
            return names[i];
          }
        }
        // Fallback - open whatever the SW is using
        return 'pwstaff-v8';
      });
    }

    function fetchOne(url, cache) {
      // X-Prefetch header tells the SW to NOT intercept this request
      // so the fetch goes directly to the network and we cache the response ourselves
      return fetch(url, {
        credentials: 'same-origin',
        headers: { 'X-Prefetch': 'true' }
      }).then(function(response) {
        completed++;
        if (response.ok && !response.redirected) {
          results.success++;
          // Build full URL for consistent cache key matching
          var fullUrl = new URL(url, location.origin).href;
          // Cache by full URL string - must match how SW looks up pages
          return cache.put(fullUrl, response);
        } else {
          results.failed++;
        }
      }).catch(function() {
        completed++;
        results.failed++;
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
      .then(function(r) { return r.json(); })
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
      '/staff/login.php',
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
          totalSteps: totalSteps
        };
      })
      .catch(function(err) {
        if (onProgress) onProgress('error', completedSteps, totalSteps, 'Error: ' + err.message);
        throw err;
      });
  }

  // ==================== PENDING ACTIONS ====================

  // Add a pending action to IndexedDB
  function addPending(type, description, payload) {
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
          error: null
        };
        var req = store.add(record);
        req.onsuccess = function() { resolve(req.result); };
        req.onerror = function() { reject(req.error); };
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
          if (!record) { reject(new Error('Record not found')); return; }
          for (var k in updates) { record[k] = updates[k]; }
          var putReq = store.put(record);
          putReq.onsuccess = function() { resolve(record); };
          putReq.onerror = function() { reject(putReq.error); };
        };
        getReq.onerror = function() { reject(getReq.error); };
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
      }).then(function(r) { return r.json(); });
    }

    var formData = new FormData();

    // Add fields
    if (payload.fields) {
      for (var key in payload.fields) {
        formData.append(key, payload.fields[key]);
      }
    }

    // Add files (stored as base64)
    if (payload.files && payload.files.length > 0) {
      for (var i = 0; i < payload.files.length; i++) {
        var f = payload.files[i];
        var blob = base64ToBlob(f.data, f.type);
        formData.append(f.key, blob, f.name);
      }
    }

    return fetch(payload.url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(function(r) { return r.json(); });
  }

  // Sync all pending records one by one
  function syncAll() {
    if (syncing) return Promise.resolve();
    if (!navigator.onLine) return Promise.resolve();

    syncing = true;
    updateSyncUI('syncing');

    return getPending().then(function(records) {
      if (records.length === 0) {
        syncing = false;
        updateSyncUI('idle');
        return;
      }

      var chain = Promise.resolve();
      records.forEach(function(record) {
        chain = chain.then(function() {
          return syncOne(record).then(function(response) {
            if (response && response.success) {
              return updateRecord(record.id, {
                status: 'synced',
                synced_at: new Date().toISOString()
              });
            } else {
              return updateRecord(record.id, {
                error: (response && response.error) || 'Sync failed'
              });
            }
          }).catch(function(err) {
            return updateRecord(record.id, {
              error: err.message || 'Network error'
            });
          }).then(function() {
            updateSyncUI('syncing');
          });
        });
      });

      return chain.then(function() {
        syncing = false;
        updateSyncUI('done');
        cleanOld();
      });
    }).catch(function() {
      syncing = false;
      updateSyncUI('idle');
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
      getPending().then(function(pending) {
        getAll().then(function(all) {
          uiCallback(state, pending.length, all);
        });
      });
    }
  }

  // Check if online and auto-sync
  function init() {
    openDB().then(function() {
      if (navigator.onLine) {
        setTimeout(function() { syncAll(); }, 2000);
      }
    });

    window.addEventListener('online', function() {
      syncAll();
    });

    // Periodic check every 30s when online
    setInterval(function() {
      if (navigator.onLine && !syncing) {
        getPending().then(function(records) {
          if (records.length > 0) syncAll();
        });
      }
    }, 30000);
  }

  return {
    init: init,
    addPending: addPending,
    getAll: getAll,
    getPending: getPending,
    syncAll: syncAll,
    fileToBase64: fileToBase64,
    onSyncUpdate: onSyncUpdate,
    cleanOld: cleanOld,
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
