(() => {
    document.addEventListener('alpine:init', () => {
        Alpine.data('collapse', (defaultValue) => ({
           selected: defaultValue,
     
           selectCollapse(content) {
              this.selected !== content ? this.selected = content : this.selected = null;
           },
           activeCollapse: (ref, content, selected) => {
              return selected == content ? `max-height: ${ref[content].scrollHeight}px` : '';
           }
        }))
    })
})();