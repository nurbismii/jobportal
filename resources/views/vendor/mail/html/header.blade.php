<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
            <img src="{{ asset('img/logo-vdni1.png') }}" class="logo" alt="PT VDNI LOGO">
            @else
            {{ $slot }}
            @endif
        </a>
    </td>
</tr>