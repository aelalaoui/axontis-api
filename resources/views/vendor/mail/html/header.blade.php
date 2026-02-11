@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img
    class="logo"
    src="{{ asset('images/emails/logo.png') }}"
    alt="Axontis Logo"
    style="height: 200px; width: 600px; max-width: 100%;"
>
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
