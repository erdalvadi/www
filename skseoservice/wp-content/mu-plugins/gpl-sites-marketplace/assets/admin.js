/**
 * GPL Sites Admin JavaScript
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        initCheckboxes();
        initActions();
        initBulkActions();
    });
    
    function initCheckboxes() {
        var checkAll = document.getElementById('gplm-check-all');
        var rowCheckboxes = document.querySelectorAll('.gplm-row-check');
        
        if (!checkAll) return;
        
        checkAll.addEventListener('change', function() {
            var checked = this.checked;
            rowCheckboxes.forEach(function(cb) {
                cb.checked = checked;
            });
            updateBulkButtons();
        });
        
        rowCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', updateBulkButtons);
        });
    }
    
    function updateBulkButtons() {
        var checked = document.querySelectorAll('.gplm-row-check:checked');
        var bulkApprove = document.getElementById('gplm-bulk-approve');
        var bulkReject = document.getElementById('gplm-bulk-reject');
        var bulkDelete = document.getElementById('gplm-bulk-delete');
        
        var hasSelection = checked.length > 0;
        
        if (bulkApprove) bulkApprove.disabled = !hasSelection;
        if (bulkReject) bulkReject.disabled = !hasSelection;
        if (bulkDelete) bulkDelete.disabled = !hasSelection;
    }
    
    function initActions() {
        document.querySelectorAll('.gplm-btn-approve').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var row = this.closest('tr');
                var siteId = row.getAttribute('data-id');
                updateSiteStatus(siteId, 'active', row, this);
            });
        });
        
        document.querySelectorAll('.gplm-btn-reject').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var row = this.closest('tr');
                var siteId = row.getAttribute('data-id');
                updateSiteStatus(siteId, 'rejected', row, this);
            });
        });
        
        document.querySelectorAll('.gplm-btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to delete this site? This cannot be undone.')) {
                    return;
                }
                var row = this.closest('tr');
                var siteId = row.getAttribute('data-id');
                deleteSite(siteId, row, this);
            });
        });
    }
    
    function initBulkActions() {
        var bulkApprove = document.getElementById('gplm-bulk-approve');
        var bulkReject = document.getElementById('gplm-bulk-reject');
        var bulkDeleteBtn = document.getElementById('gplm-bulk-delete');
        
        if (bulkApprove) {
            bulkApprove.addEventListener('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                
                if (!confirm('Approve ' + ids.length + ' site(s)?')) return;
                
                bulkUpdateStatus(ids, 'active', this);
            });
        }
        
        if (bulkReject) {
            bulkReject.addEventListener('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                
                if (!confirm('Reject ' + ids.length + ' site(s)?')) return;
                
                bulkUpdateStatus(ids, 'rejected', this);
            });
        }
        
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                
                if (!confirm('DELETE ' + ids.length + ' site(s)? This cannot be undone!')) return;
                
                bulkDeleteSites(ids, this);
            });
        }
    }
    
    function getSelectedIds() {
        var ids = [];
        document.querySelectorAll('.gplm-row-check:checked').forEach(function(cb) {
            var row = cb.closest('tr');
            if (row) {
                ids.push(row.getAttribute('data-id'));
            }
        });
        return ids;
    }
    
    function updateSiteStatus(siteId, status, row, btn) {
        if (!siteId || !status) return;
        
        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<span class="dashicons dashicons-update spin"></span>';
            btn.disabled = true;
        }
        
        var fd = new FormData();
        fd.append('action', 'gpl_admin_update_status');
        fd.append('nonce', GPLM.nonce);
        fd.append('site_id', siteId);
        fd.append('status', status);
        
        fetch(GPLM.ajax, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var statusCell = row.querySelector('.column-status .gplm-status');
                if (statusCell) {
                    statusCell.className = 'gplm-status gplm-status-' + status;
                    statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                }
                
                var approveBtn = row.querySelector('.gplm-btn-approve');
                var rejectBtn = row.querySelector('.gplm-btn-reject');
                
                if (status === 'active') {
                    if (approveBtn) approveBtn.style.display = 'none';
                    if (rejectBtn) rejectBtn.style.display = '';
                } else if (status === 'rejected') {
                    if (approveBtn) approveBtn.style.display = '';
                    if (rejectBtn) rejectBtn.style.display = 'none';
                } else {
                    if (approveBtn) approveBtn.style.display = '';
                    if (rejectBtn) rejectBtn.style.display = '';
                }
                
                showNotice('success', data.data.message || 'Status updated');
                
                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            } else {
                showNotice('error', data.data?.message || 'Failed to update status');
                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
            showNotice('error', 'Network error. Please try again.');
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    }
    
    function deleteSite(siteId, row, btn) {
        if (!siteId) return;
        
        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<span class="dashicons dashicons-update spin"></span>';
            btn.disabled = true;
        }
        
        var fd = new FormData();
        fd.append('action', 'gpl_admin_delete_site');
        fd.append('nonce', GPLM.nonce);
        fd.append('site_id', siteId);
        
        fetch(GPLM.ajax, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                row.style.transition = 'all 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(function() {
                    row.remove();
                    updateStats();
                }, 300);
                showNotice('success', data.data.message || 'Site deleted');
            } else {
                showNotice('error', data.data?.message || 'Failed to delete site');
                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
            showNotice('error', 'Network error. Please try again.');
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    }
    
    function bulkUpdateStatus(ids, status, btn) {
        if (!ids || ids.length === 0) return;
        
        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = 'Processing...';
            btn.disabled = true;
        }
        
        var fd = new FormData();
        fd.append('action', 'gpl_admin_bulk_update');
        fd.append('nonce', GPLM.nonce);
        fd.append('site_ids', JSON.stringify(ids));
        fd.append('status', status);
        
        fetch(GPLM.ajax, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showNotice('success', data.data.message || 'Sites updated');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showNotice('error', data.data?.message || 'Failed to update sites');
                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
            showNotice('error', 'Network error. Please try again.');
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    }
    
    function bulkDeleteSites(ids, btn) {
        if (!ids || ids.length === 0) return;
        
        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = 'Deleting...';
            btn.disabled = true;
        }
        
        var fd = new FormData();
        fd.append('action', 'gpl_admin_bulk_delete');
        fd.append('nonce', GPLM.nonce);
        fd.append('site_ids', JSON.stringify(ids));
        
        fetch(GPLM.ajax, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showNotice('success', data.data.message || 'Sites deleted');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showNotice('error', data.data?.message || 'Failed to delete sites');
                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
            showNotice('error', 'Network error. Please try again.');
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    }
    
    function updateStats() {
        var tbody = document.querySelector('.gplm-sites-table tbody');
        if (tbody && tbody.children.length === 0) {
            var noDataRow = document.createElement('tr');
            noDataRow.innerHTML = '<td colspan="10" class="gplm-no-sites">No sites found.</td>';
            tbody.appendChild(noDataRow);
        }
    }
    
    function showNotice(type, message) {
        var existing = document.querySelector('.gplm-notice');
        if (existing) {
            existing.remove();
        }
        
        var notice = document.createElement('div');
        notice.className = 'gplm-notice gplm-notice-' + type;
        notice.innerHTML = '<p>' + message + '</p>';
        notice.style.cssText = 'position:fixed;top:40px;right:20px;padding:12px 20px;border-radius:8px;z-index:99999;max-width:400px;box-shadow:0 4px 12px rgba(0,0,0,0.15);animation:slideIn 0.3s ease;';
        
        if (type === 'success') {
            notice.style.background = '#d1fae5';
            notice.style.color = '#065f46';
            notice.style.border = '1px solid #6ee7b7';
        } else {
            notice.style.background = '#fee2e2';
            notice.style.color = '#991b1b';
            notice.style.border = '1px solid #fca5a5';
        }
        
        document.body.appendChild(notice);
        
        setTimeout(function() {
            notice.style.opacity = '0';
            notice.style.transition = 'opacity 0.3s';
            setTimeout(function() {
                notice.remove();
            }, 300);
        }, 3000);
    }
    
    var style = document.createElement('style');
    style.textContent = '@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}.spin{animation:spin 1s linear infinite}@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
    document.head.appendChild(style);
})();
