<script>
@if ($theme)
    window.localStorage.setItem("__theme:mode__", "{{ $theme }}");
@endif
@if ($language)
    window.localStorage.setItem("__language:type__", "{{ $language }}");
@endif
</script>
