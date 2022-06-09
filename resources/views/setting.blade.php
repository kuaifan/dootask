<script>
@if ($theme)
    window.localStorage['__theme:mode__'] = "{{ $theme }}";
@endif
@if ($language)
    window.localStorage['__language:type__'] = "{{ $language }}";
@endif
</script>
