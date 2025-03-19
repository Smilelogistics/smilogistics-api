@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="{{asset('images/logo/smile-logistics-dark.png')}}" class="logo" alt="smile Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
