@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="{{ config('app.url') }}/images/emails/logo.png" class="logo" alt="Axontis Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
