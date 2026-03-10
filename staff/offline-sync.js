/**
 * PWSTAFF Offline Sync Library
 * Stores pending uploads in IndexedDB when offline.
 * Auto-syncs one-by-one when back online.
 */
var OfflineSync = (function() {
  var DB_NAME = 'pwstaff_sync';
  var DB_VERSION = 1;
  var STORE_NAME = 'pending_actions';
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
      };
      request.onsuccess = function(e) {
        db = e.target.result;
        resolve(db);
      };
      request.onerror = function(e) { reject(e.target.error); };
    });
  }

  // Add a pending action to IndexedDB
  // type: 'photo_upload' | 'install_upload' | 'signature' | 'job_done'
  // payload: { url, fields, files[] }
  //   fields: key-value pairs for FormData
  //   files: [{ key, data (base64), name, type }]
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

    // Add body string if present (for signature)
    if (payload.body) {
      return fetch(payload.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.body
      }).then(function(r) { return r.json(); });
    }

    return fetch(payload.url, {
      method: 'POST',
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
            if (response.success) {
              return updateRecord(record.id, {
                status: 'synced',
                synced_at: new Date().toISOString()
              });
            } else {
              return updateRecord(record.id, {
                error: response.error || 'Sync failed'
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
    cleanOld: cleanOld
  };
})();

// Initialize on load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() { OfflineSync.init(); });
} else {
  OfflineSync.init();
}
