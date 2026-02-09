// app.js
// Shared JS for pages that use the Slick hero slider.
// Safe guards so nothing breaks on pages without .hero-slide.

(function () {
  function initHeroSlider() {
    if (!window.jQuery) return;
    var $ = window.jQuery;
    if (!$('.hero-slide').length) return;
    if (typeof $('.hero-slide').slick !== 'function') return;

    $('.hero-slide').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      autoplay: true,
      infinite: true,
      autoplaySpeed: 5600,
      arrows: false,
      speed: 3800,
      fade: true,
      cssEase: 'linear'
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeroSlider);
  } else {
    initHeroSlider();
  }
})();
