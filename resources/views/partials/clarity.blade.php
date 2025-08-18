{{-- @if(app()->isProduction() && config('services.clarity.id')) --}}
@if(config('services.clarity.id'))
<script>
  (function(c,l,a,r,i,t,y){
      c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
      t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/{{ config('services.clarity.id') }}";
      y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
  })(window, document, "clarity", "script");

  @if(auth()->check())
    clarity("set", "user_id", "{{ auth()->id() }}");
    clarity("set", "user_name", @json(auth()->user()->name));
    clarity("set", "user_email", @json(auth()->user()->email));
    @php $roles = method_exists(auth()->user(),'getRoleNames') ? auth()->user()->getRoleNames()->implode(',') : ''; @endphp
    clarity("set", "user_roles", @json($roles));
  @endif
</script>
@endif

