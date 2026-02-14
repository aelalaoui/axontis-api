@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <div style="margin-bottom: 30px;">
                    <h1 style="font-family: 'Orbitron', sans-serif; font-size: 48px; font-weight: 700; color: #ffffff; letter-spacing: 1px; text-align: center; margin: 0 0 20px 0; padding: 0;">
                        AXONTIS
                    </h1>
                    <div style="width: 128px; height: 4px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); margin: 0 auto; border-radius: 9999px;"></div>
                </div>
            @else
                {!! $slot !!}
            @endif
        </a>
    </td>
</tr>
