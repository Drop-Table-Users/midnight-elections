<div class="admin-language-switcher" style="display: inline-flex; gap: 0.5rem; align-items: center;">
    @php
        $currentLocale = session('admin_locale', app()->getLocale());
    @endphp

    @if($currentLocale === 'sk')
        <span class="locale-current" style="color: white; font-weight: 700; padding: 0.5rem 1rem; border-radius: 4px; background-color: rgba(255,255,255,0.3); font-size: 14px;">
            SK
        </span>
        <a href="{{ route('admin.locale.switch', 'en') }}"
           class="locale-link"
           style="color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; background-color: rgba(255,255,255,0.1); transition: all 0.2s; font-size: 14px; font-weight: 500;"
           onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'"
           onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'">
            EN
        </a>
    @else
        <a href="{{ route('admin.locale.switch', 'sk') }}"
           class="locale-link"
           style="color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; background-color: rgba(255,255,255,0.1); transition: all 0.2s; font-size: 14px; font-weight: 500;"
           onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'"
           onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'">
            SK
        </a>
        <span class="locale-current" style="color: white; font-weight: 700; padding: 0.5rem 1rem; border-radius: 4px; background-color: rgba(255,255,255,0.3); font-size: 14px;">
            EN
        </span>
    @endif
</div>
