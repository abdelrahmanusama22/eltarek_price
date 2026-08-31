document.addEventListener('DOMContentLoaded', () => {
    const attachDragToScroll = () => {
        const sliders = document.querySelectorAll('.fi-ta-content, .fi-ta-table-container, .overflow-x-auto');
        
        sliders.forEach(slider => {
            let isDown = false;
            let startX;
            let scrollLeft;

            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                slider.style.cursor = 'grabbing';
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
            });
            
            slider.addEventListener('mouseleave', () => {
                isDown = false;
                slider.style.cursor = 'default';
            });
            
            slider.addEventListener('mouseup', () => {
                isDown = false;
                slider.style.cursor = 'default';
            });
            
            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 1.5; // Scroll-fast multiplier
                slider.scrollLeft = scrollLeft - walk;
            });
        });
    };

    // Run initially
    attachDragToScroll();

    // Re-run on Livewire navigations/updates
    document.addEventListener('livewire:navigated', attachDragToScroll);
    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('morph.updated', () => {
            attachDragToScroll();
        });
    });
});
