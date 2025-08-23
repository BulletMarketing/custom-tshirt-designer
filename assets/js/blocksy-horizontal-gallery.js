/**
 * Custom T-Shirt Designer - Blocksy Theme Horizontal Gallery Scripts
 */
const jQuery = window.jQuery // Declare the jQuery variable
jQuery(document).ready(($) => {
  // Wait for the gallery to be fully loaded
  setTimeout(() => {
    // Get the thumbnails container - Blocksy uses .flexy-pills
    const thumbnailsContainer = $(".ct-product-view .flexy-pills, .product-entry-wrapper .flexy-pills")

    if (thumbnailsContainer.length) {
      console.log("Blocksy gallery found, initializing horizontal gallery")

      // Make sure the container has proper styling
      thumbnailsContainer.css({
        display: "flex",
        "flex-direction": "row",
        "overflow-x": "auto",
        "overflow-y": "hidden",
        "white-space": "nowrap",
      })

      // Get the thumbnails list
      const thumbnailsList = thumbnailsContainer.find("ol")

      // Make sure the list has proper styling
      thumbnailsList.css({
        display: "flex",
        "flex-direction": "row",
        "flex-wrap": "nowrap",
        width: "auto",
        "max-width": "none",
      })

      // Get all thumbnails
      const thumbnails = thumbnailsList.find("li")

      // Make sure thumbnails have proper styling
      thumbnails.css({
        float: "none",
        display: "inline-block",
        width: "auto",
        margin: "0 5px 0 0",
        padding: "0",
        "vertical-align": "middle",
        flex: "0 0 auto",
      })

      // Add click handler to scroll to active thumbnail
      thumbnailsContainer.find("li button").on("click", () => {
        setTimeout(() => {
          const activeThumb = thumbnailsContainer.find("li.active")
          if (activeThumb.length) {
            // Calculate position to scroll to (center the active thumbnail)
            const containerWidth = thumbnailsContainer.width()
            const thumbPos = activeThumb.position().left
            const thumbWidth = activeThumb.width()
            const scrollPos = thumbPos - containerWidth / 2 + thumbWidth / 2

            // Scroll to the active thumbnail smoothly
            thumbnailsContainer.animate(
              {
                scrollLeft: scrollPos,
              },
              300,
            )
          }
        }, 300) // Wait for the gallery to update
      })

      // Initial scroll to active thumbnail
      const activeThumb = thumbnailsContainer.find("li.active")
      if (activeThumb.length) {
        const containerWidth = thumbnailsContainer.width()
        const thumbPos = activeThumb.position().left
        const thumbWidth = activeThumb.width()
        const scrollPos = thumbPos - containerWidth / 2 + thumbWidth / 2

        thumbnailsContainer.scrollLeft(scrollPos)
      }

      // Add touch swipe support for mobile
      let isDown = false
      let startX
      let scrollLeft

      thumbnailsContainer.on("mousedown touchstart", (e) => {
        isDown = true
        startX = e.type === "touchstart" ? e.originalEvent.touches[0].pageX : e.pageX
        scrollLeft = thumbnailsContainer.scrollLeft()
        e.preventDefault()
      })

      thumbnailsContainer.on("mouseleave touchend", () => {
        isDown = false
      })

      thumbnailsContainer.on("mouseup touchend", () => {
        isDown = false
      })

      thumbnailsContainer.on("mousemove touchmove", (e) => {
        if (!isDown) return
        const x = e.type === "touchmove" ? e.originalEvent.touches[0].pageX : e.pageX
        const walk = (x - startX) * 2 // Scroll speed multiplier
        thumbnailsContainer.scrollLeft(scrollLeft - walk)
      })

      // Add MutationObserver to handle dynamic gallery changes
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.type === "attributes" || mutation.type === "childList") {
            // Re-apply styles to ensure horizontal layout
            thumbnailsList.css({
              display: "flex",
              "flex-direction": "row",
              "flex-wrap": "nowrap",
              width: "auto",
              "max-width": "none",
            })

            // Scroll to active thumbnail
            const activeThumb = thumbnailsContainer.find("li.active")
            if (activeThumb.length) {
              const containerWidth = thumbnailsContainer.width()
              const thumbPos = activeThumb.position().left
              const thumbWidth = activeThumb.width()
              const scrollPos = thumbPos - containerWidth / 2 + thumbWidth / 2

              thumbnailsContainer.scrollLeft(scrollPos)
            }
          }
        })
      })

      // Start observing the gallery
      observer.observe(thumbnailsContainer[0], {
        attributes: true,
        childList: true,
        subtree: true,
      })
    }
  }, 1000) // Wait longer for Blocksy to initialize
})
