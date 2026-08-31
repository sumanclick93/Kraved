<?php
/** @var string $icon */
$icon = $icon ?? 'pin';
?>
<?php if ($icon === 'phone'): ?>
<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6.5 3.5 9 6.2c.3.3.3.8 0 1.1L7.8 8.5c-.3.3-.3.7 0 1 1.4 1.6 3.1 3.3 4.7 4.7.3.3.7.3 1 0l1.2-1.2c.3-.3.8-.3 1.1 0l2.7 2.5c.4.4.4 1 0 1.4l-1.3 1.3c-.8.8-2 .9-3.1.4-2.6-1.2-5.3-3.4-7.6-7.6-.5-1.1-.4-2.3.4-3.1l1.3-1.3c.4-.4 1-.4 1.4 0Z"/></svg>
<?php elseif ($icon === 'truck'): ?>
<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M5 17H3V7l3-3h6l2 3h4v10h-2"/><path d="M9 4v3h5"/></svg>
<?php elseif ($icon === 'bag'): ?>
<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 7h12l-1 12H7L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>
<?php elseif ($icon === 'percent'): ?>
<span class="feature-glyph--pct" style="font-size:1.4rem;font-weight:700;line-height:1">%</span>
<?php elseif ($icon === 'store'): ?>
<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 10h16v10H4z"/><path d="M8 10V7l4-3 4 3v3"/><path d="M10 20v-5h4v5"/></svg>
<?php elseif ($icon === 'chef'): ?>
<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 11c0-2.5 1.8-4.5 4-4.5s4 2 4 4.5"/><path d="M6 11h12v2a6 6 0 0 1-12 0v-2Z"/><path d="M12 6.5V4"/></svg>
<?php else: ?>
<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/></svg>
<?php endif; ?>
