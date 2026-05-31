@php
    $themeData = \App\Services\CrownThemeResolver::viewData();
@endphp
<style id="crown-theme-variables">
    :root {
        @foreach ($themeData['css_light'] as $var => $value)
        {{ $var }}: {{ $value }};
        @endforeach
    }
    .dark {
        @foreach ($themeData['css_dark'] as $var => $value)
        {{ $var }}: {{ $value }};
        @endforeach
    }
</style>
