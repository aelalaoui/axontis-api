@props(['url'])
<tr>
    <td class="header" style="text-align: center; background: rgba(30, 41, 59, 0.8); border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
        <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
             <!-- AXONTIS Logo Image - 100% Compatible avec tous les clients email -->
            <img src="{{ asset('images/email-logo-axontis.png') }}"
                 alt="AXONTIS"
                 width="800"
                 height="200"
                 style="display: block; margin: 0 auto 18px auto;"
            >
        </a>
    </td>
</tr>
