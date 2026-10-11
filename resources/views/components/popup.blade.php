{{-- Custom Popup Component --}}
<div x-data="popupManager()" 
     x-cloak
     @popup-show.window="showPopup($event.detail)"
     @popup-confirm.window="showConfirm($event.detail)"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-show="isVisible"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
    
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" 
         @click="type === 'confirm' ? null : close()"></div>
    
    {{-- Popup Container --}}
    <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden"
         x-show="isVisible"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="type === 'confirm' ? null : close()">
        
        {{-- Icon & Color Bar --}}
        <div class="h-2" :class="{
            'bg-green-500': type === 'success',
            'bg-red-500': type === 'error',
            'bg-yellow-500': type === 'warning',
            'bg-blue-500': type === 'info',
            'bg-orange-500': type === 'confirm'
        }"></div>
        
        {{-- Content --}}
        <div class="p-6">
            <div class="flex items-start gap-4">
                {{-- Icon --}}
                <div class="shrink-0 w-12 h-12 rounded-full flex items-center justify-center" :class="{
                    'bg-green-100': type === 'success',
                    'bg-red-100': type === 'error',
                    'bg-yellow-100': type === 'warning',
                    'bg-blue-100': type === 'info',
                    'bg-orange-100': type === 'confirm'
                }">
                    {{-- Success Icon --}}
                    <svg x-show="type === 'success'" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    
                    {{-- Error Icon --}}
                    <svg x-show="type === 'error'" class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    
                    {{-- Warning Icon --}}
                    <svg x-show="type === 'warning'" class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    
                    {{-- Info Icon --}}
                    <svg x-show="type === 'info'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    
                    {{-- Confirm Icon --}}
                    <svg x-show="type === 'confirm'" class="w-6 h-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                
                {{-- Text Content --}}
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 mb-1" x-text="title"></h3>
                    <div class="text-sm text-gray-600 whitespace-pre-line" x-html="message"></div>
                    
                    {{-- Additional HTML content (for links, lists, etc.) --}}
                    <div x-show="htmlContent" x-html="htmlContent" class="mt-3"></div>
                </div>
            </div>
            
            {{-- Actions --}}
            <div class="mt-6 flex items-center justify-end gap-3">
                {{-- Cancel button (for confirm dialogs) --}}
                <button x-show="type === 'confirm'" 
                        @click="cancel()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                
                {{-- Main action button --}}
                <button @click="confirm()"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                        :class="{
                            'bg-green-600 hover:bg-green-700': type === 'success',
                            'bg-red-600 hover:bg-red-700': type === 'error',
                            'bg-yellow-600 hover:bg-yellow-700': type === 'warning',
                            'bg-blue-600 hover:bg-blue-700': type === 'info',
                            'bg-orange-600 hover:bg-orange-700': type === 'confirm'
                        }"
                        x-text="confirmText">
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function popupManager() {
    return {
        isVisible: false,
        type: 'info',
        title: '',
        message: '',
        htmlContent: '',
        confirmText: 'OK',
        onConfirm: null,
        onCancel: null,
        
        showPopup(data) {
            this.type = data.type || 'info';
            this.title = data.title || this.getDefaultTitle(data.type);
            this.message = data.message || '';
            this.htmlContent = data.html || '';
            this.confirmText = data.confirmText || this.getDefaultConfirmText(data.type);
            this.onConfirm = data.onConfirm || null;
            this.onCancel = data.onCancel || null;
            this.isVisible = true;
        },
        
        showConfirm(data) {
            this.type = 'confirm';
            this.title = data.title || 'Confirm Action';
            this.message = data.message || 'Are you sure you want to proceed?';
            this.htmlContent = data.html || '';
            this.confirmText = data.confirmText || 'Confirm';
            this.onConfirm = data.onConfirm || null;
            this.onCancel = data.onCancel || null;
            this.isVisible = true;
        },
        
        confirm() {
            if (this.onConfirm && typeof this.onConfirm === 'function') {
                this.onConfirm();
            }
            this.close();
        },
        
        cancel() {
            if (this.onCancel && typeof this.onCancel === 'function') {
                this.onCancel();
            }
            this.close();
        },
        
        close() {
            this.isVisible = false;
            setTimeout(() => {
                this.reset();
            }, 300);
        },
        
        reset() {
            this.type = 'info';
            this.title = '';
            this.message = '';
            this.htmlContent = '';
            this.confirmText = 'OK';
            this.onConfirm = null;
            this.onCancel = null;
        },
        
        getDefaultTitle(type) {
            const titles = {
                success: 'Success',
                error: 'Error',
                warning: 'Warning',
                info: 'Information',
                confirm: 'Confirm Action'
            };
            return titles[type] || 'Notification';
        },
        
        getDefaultConfirmText(type) {
            if (type === 'confirm') return 'Confirm';
            return 'OK';
        }
    }
}

// Global helper functions
window.showPopup = function(type, title, message, html = '') {
    window.dispatchEvent(new CustomEvent('popup-show', {
        detail: { type, title, message, html }
    }));
};

window.showSuccess = function(title, message) {
    showPopup('success', title, message);
};

window.showError = function(title, message) {
    showPopup('error', title, message);
};

window.showWarning = function(title, message) {
    showPopup('warning', title, message);
};

window.showInfo = function(title, message) {
    showPopup('info', title, message);
};

window.showConfirm = function(title, message, onConfirm, onCancel = null, html = '') {
    window.dispatchEvent(new CustomEvent('popup-confirm', {
        detail: { 
            title, 
            message, 
            html,
            confirmText: 'Confirm',
            onConfirm, 
            onCancel 
        }
    }));
};
</script>
