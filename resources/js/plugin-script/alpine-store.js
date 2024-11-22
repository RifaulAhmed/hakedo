document.addEventListener("alpine:init", () => {
   Alpine.store("main", {
      newMessage: "hello world",
      darkMode: true,
      isCompact: () => {
         if (localStorage.getItem('compactSidebar')) {
            return true;
         }else {
            return false;
         }
      }, 
      
      selected: null,
      // selectedCollapse: (value) => {
      //    this.selected = value;
      // },
      selectCollapse: (value) => {
         this.selected !== value ? this.selected = value : this.selected = null
         console.log(this.selected);
      },
      activeCollapse: (ref, content) => {
         console.log(ref, content);
         return 'max-height: ' + ref[`${content}`].scrollHeight + 'px';
      }
   });
});
console.log('I am from alpine js store');