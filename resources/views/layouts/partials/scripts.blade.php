<!-- Essential Scripts Only -->
<script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/app.min.js') }}"></script>
<!-- solar icons -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

<!-- Optimized Admin Scripts -->
<link rel="stylesheet" href="{{ asset('assets/css/admin-sidebar.min.css') }}">
<script src="{{ asset('assets/js/admin-optimized.min.js') }}"></script>

<!-- Additional Custom Styles (if needed) -->
<style>
/* Only essential overrides here */
</style>

<!-- Livewire Scripts -->
@livewireScripts

<!-- File Download Handler -->
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('downloadFile', (data) => {
        const { filename, content, mimeType } = data;
        
        // إنشاء blob من المحتوى
        const blob = new Blob([content], { type: mimeType });
        
        // إنشاء رابط تحميل
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        
        // إضافة الرابط إلى الصفحة وتنشيطه
        document.body.appendChild(link);
        link.click();
        
        // تنظيف
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    });
});
</script>

