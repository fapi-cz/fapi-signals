document.addEventListener('DOMContentLoaded', function () {
  function toggleSection(section) {
    var toggle = section.querySelector('[data-toggle-target]');
    var target = section.querySelector('[data-target]');
    if (!toggle || !target) return;
    var enabled = toggle.checked;
    target.classList.toggle('is-hidden', !enabled);
  }

  var sections = document.querySelectorAll('.fapi-section[data-section]');
  Array.prototype.forEach.call(sections, function (section) {
    var toggle = section.querySelector('[data-toggle-target]');
    if (toggle) {
      toggle.addEventListener('change', function () {
        toggleSection(section);
      });
    }
    toggleSection(section);
  });
});
