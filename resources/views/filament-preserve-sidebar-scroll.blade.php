<script>
  // حفظ scrollX لمنع إعادة التمرير التلقائي
  document.addEventListener("livewire:navigated", () => {
    document.body.setAttribute("data-scroll-x", window.scrollX);
  });

  // حفظ موضع sidebar scroll قبل التنقل
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.fi-sidebar-item-button');
    const nav = document.querySelector('.fi-sidebar-nav');
    if (btn && nav) {
      localStorage.setItem('sidebar-scroll', nav.scrollTop);
    }
  });

  // إعادة التمرير للـ sidebar بعد التنقل
  document.addEventListener('livewire:navigated', () => {
    const nav = document.querySelector('.fi-sidebar-nav');
    const saved = Number(localStorage.getItem('sidebar-scroll'));
    if (nav && !isNaN(saved)) {
      setTimeout(() => nav.scrollTop = saved, 50);
    }
  });
</script>
