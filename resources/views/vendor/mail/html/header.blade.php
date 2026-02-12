@props(['url'])
<tr>
    <td class="header" style="text-align: center; padding: 40px 20px; background: rgba(30, 41, 59, 0.8); border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
        @if (trim($slot) === 'Laravel')
            <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
                <!-- AXONTIS Logo Text with Wide Letter Spacing -->
                <h1 style="font-family: 'Orbitron', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 44px; font-weight: 700; color: #ffffff; letter-spacing: 10px; margin: 0 0 18px 0; padding: 0; text-transform: uppercase; line-height: 1; text-decoration: none;">
                    AXONTIS
                </h1>

                <!-- Orange Underline - Centered and Sleek -->
                <div style="width: 120px; height: 3px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); margin: 0 auto; border-radius: 9999px;"></div>
            </a>
        @else
            <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
                {!! $slot !!}
            </a>
        @endif
    </td>
</tr>
