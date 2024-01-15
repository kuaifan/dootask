<script>
@if ($theme)
    window.localStorage.setItem("__theme:mode__", "{{ $theme }}");
@endif
@if ($language)
    window.localStorage.setItem("__language:type__", "{{ $language }}");
@endif
@if ($userid)
    window.localStorage.setItem("__user:userid__", "{{ $userid }}");
@endif
@if ($token)
    window.localStorage.setItem("__user:token__", "{{ $token }}");
@endif
</script>
