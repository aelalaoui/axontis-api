@props(['url'])
<tr>
    <td class="header" style="text-align: center; background: rgba(30, 41, 59, 0.8); border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
        <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
            @if (trim($slot) === 'AXONTIS')
                <!-- AXONTIS Logo Image - 100% Compatible avec tous les clients email -->
                <img src="{{ asset('images/email-logo-axontis.png') }}"
                     alt="AXONTIS"
                     width="800"
                     height="200"
                     style="display: block; margin: 0 auto 18px auto; max-width: 800px; height: auto; border: 0;">

                <!-- Orange Underline - Centered and Sleek -->
                <div style="width: 120px; height: 3px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); margin: 0 auto; border-radius: 9999px;"></div>
            @else
                {!! $slot !!}
            @endif
        </a>
    </td>
</tr>
