@php
    $themeData = \App\Services\CrownThemeResolver::viewData();
    $themeMode = $themeData['theme_mode'];
@endphp
<script>
    (function () {
        localStorage.setItem('theme', @json($themeMode));
    })();
</script>
