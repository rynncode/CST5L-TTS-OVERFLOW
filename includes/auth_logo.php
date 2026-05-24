<?php
/**
 * includes/auth_logo.php
 *
 * Shared logo mark used on login.php and register.php.
 * Drop-in replacement for the repeated inline <div class="auth-logo"> block.
 */
?>
<div class="auth-logo">
  <div class="logo-mark" style="gap:.6rem;">
    <svg width="40" height="40" viewBox="0 0 32 32" fill="none" style="flex-shrink:0;">
      <defs>
        <linearGradient id="alg1" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
          <stop offset="0%"   stop-color="#7c5cfc"/>
          <stop offset="100%" stop-color="#a286ff"/>
        </linearGradient>
        <linearGradient id="alg2" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
          <stop offset="0%"   stop-color="#a286ff" stop-opacity="0.85"/>
          <stop offset="100%" stop-color="#c4aaff" stop-opacity="0.3"/>
        </linearGradient>
      </defs>
      <rect x="5" y="15" width="22" height="13" rx="3.5" fill="url(#alg1)" opacity="0.12" stroke="url(#alg1)" stroke-width="1.2"/>
      <path d="M5 18 Q9.5 13 16 16 Q22.5 19 27 14" stroke="url(#alg1)" stroke-width="1.7" fill="none" stroke-linecap="round"/>
      <path d="M8 15 C8 12 9.5 10 8.5 7.5"    stroke="url(#alg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
      <path d="M16 16 C16 13 17.5 11 16.5 8.5" stroke="url(#alg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
      <path d="M24 14 C24 11 25.5 9 24.5 6.5"  stroke="url(#alg2)" stroke-width="1.3" fill="none" stroke-linecap="round"/>
      <circle cx="16" cy="16" r="2" fill="url(#alg1)" opacity="0.95"/>
    </svg>
    OVER<span class="accent">FLOW</span>
  </div>
</div>
