/**
 * Custom T-Shirt Designer - Horizontal Gallery Scripts
 */
const jQuery = window.jQuery // Declare the jQuery variable
jQuery(document).ready(($) => {
  // Wait for the gallery to be fully loaded
  setTimeout(() => {
    // Get the thumbnails container
    const thumbnailsContainer = $(".woocommerce-product-gallery .flex-control-nav")

    if (thumbnailsContainer.length) {
      // Make sure the container has proper styling
      thumbnailsContainer.css({
        display: "flex",
        "flex-direction": "row",
        "overflow-x": "auto",
        "overflow-y": "hidden",
        "white-space": "nowrap",
      })

      // Get all thumbnails
      const thumbnails = thumbnailsContainer.find("li")

      // Add click handler to scroll to active thumbnail
      $(".woocommerce-product-gallery .flex-control-paging a, .woocommerce-product-gallery .flex-direction-nav a").on(
        "click",
        () => {
          setTimeout(() => {
            const activeThumb = thumbnailsContainer.find("img.flex-active").parent()
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
        },
      )

      // Initial scroll to active thumbnail
      const activeThumb = thumbnailsContainer.find("img.flex-active").parent()
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
    }
  }, 500) // Wait for gallery to initialize
})
