/**
 * Custom T-Shirt Designer Frontend Scripts
 */
jQuery(document).ready(($) => {
  // Variables
  const designer = $("#ctd-designer")
  if (!designer.length) return

  const productId = designer.data("product-id")
  const productType = designer.data("product-type")
  const minQuantity = typeof ctd_params !== "undefined" ? ctd_params.min_quantity : 20 // Provide a default value
  const productPrice = Number.parseFloat($("#ctd-product-price").val()) || 0

  // Get product images from hidden fields
  const frontImage = $("#ctd-front-image").val()
  const backImage = $("#ctd-back-image").val()
  const sideImage = $("#ctd-side-image").val()

  // Get setup fees and tier pricing from hidden fields
  let setupFees = {}
  try {
    setupFees = JSON.parse($("#ctd-setup-fees").val()) || {}
  } catch (e) {
    console.error("Error parsing setup fees:", e)
    setupFees = {
      screen_printing: 10.95,
      dtg: 8.95,
      embroidery: 15.95,
      heat_transfer: 7.95,
    }
  }

  let tierPricing = []
  try {
    tierPricing = JSON.parse($("#ctd-tier-pricing").val()) || []
  } catch (e) {
    console.error("Error parsing tier pricing:", e)
    tierPricing = []
  }

  // Sort tier pricing by min quantity
  tierPricing.sort((a, b) => a.min - b.min)

  const designData = {
    colors: [],
    quantities: {},
    positions: [],
    designs: {},
    decoration_method: "", // Single decoration method for all positions
    design_positions: {}, // Store position and scale of designs
  }

  // Initialize WooCommerce image overlay
  let currentPosition = "front"

  // Delay the setup to ensure WooCommerce gallery is fully loaded
  setTimeout(() => {
    setupWooCommerceOverlay()
  }, 500)

  // Color selection
  const multiColorToggle = $("#ctd-multi-color")
  const colorInputs = $(".ctd-color-input")

  // Handle color selection
  colorInputs.on("change", function () {
    const colorValue = $(this).val()
    const isChecked = $(this).prop("checked")

    // If multi-color is not checked, uncheck all other colors
    if (!multiColorToggle.prop("checked") && isChecked) {
      colorInputs.not(this).prop("checked", false)
    }

    // Update design data
    updateColorSelection()

    // Update size tables
    updateSizeTables()
  })

  // Handle multi-color toggle
  multiColorToggle.on("change", function () {
    // If multi-color is unchecked and multiple colors are selected, keep only the first one
    if (!$(this).prop("checked")) {
      const checkedColors = colorInputs.filter(":checked")
      if (checkedColors.length > 1) {
        checkedColors.not(":first").prop("checked", false)
      }

      // Update design data
      updateColorSelection()

      // Update size tables
      updateSizeTables()
    }
  })

  // Update color selection in design data
  function updateColorSelection() {
    // Store previous colors to check for removed colors
    const previousColors = [...designData.colors]

    // Update colors array
    designData.colors = []
    colorInputs.filter(":checked").each(function () {
      designData.colors.push($(this).val())
    })

    // Update hidden field
    updateDesignData()

    // Update size tables
    updateSizeTables()
  }

  // Update size tables based on selected colors
  function updateSizeTables() {
    const sizeTables = $(".ctd-size-tables-container")
    sizeTables.empty()

    // If no colors selected, show message instead of default table
    if (designData.colors.length === 0) {
      sizeTables.html(
        '<p class="ctd-select-color-message">' +
          (typeof ctd_params !== "undefined" && ctd_params.i18n && ctd_params.i18n.select_color
            ? ctd_params.i18n.select_color
            : "Please select a color to view size options.") +
          "</p>",
      )
      return
    }

    // Create table for each selected color
    designData.colors.forEach((color) => {
      const colorName = $('.ctd-color-input[value="' + color + '"]')
        .siblings(".ctd-color-name")
        .text()
      sizeTables.append(createSizeTable(color, colorName))
    })

    // Bind quantity change event
    $(".ctd-quantity-input").on("change", updateTotalQuantity)

    // Also update on keyup for better responsiveness with text inputs
    $(".ctd-quantity-input").on("keyup", updateTotalQuantity)

    // Style the quantity inputs
    $(".ctd-quantity-input").css({
      width: "50px",
      "text-align": "center",
      padding: "5px",
      border: "1px solid #ddd",
    })

    // Update total quantity
    updateTotalQuantity()

    // Clean up quantities for deselected colors
    const currentColors = designData.colors
    Object.keys(designData.quantities).forEach((color) => {
      if (color !== "default" && !currentColors.includes(color)) {
        delete designData.quantities[color]
      }
    })
  }

  // Create size table for a color
  function createSizeTable(color, colorName) {
    const tableWrapper = $('<div class="ctd-size-table-wrapper" data-color="' + color + '"></div>')

    // Add color name if not default
    if (color !== "default") {
      tableWrapper.append("<h5>" + colorName + "</h5>")
    }

    // Create table
    const table = $('<table class="ctd-size-table"></table>')

    // Add header row
    const headerRow = $("<tr></tr>")
    headerRow.append(
      "<th>" +
        (typeof ctd_params !== "undefined" && ctd_params.i18n && ctd_params.i18n.size ? ctd_params.i18n.size : "Size") +
        "</th>",
    )

    // Get color-specific sizes if available
    let sizes = []
    let colorSizes = {}

    try {
      colorSizes = JSON.parse($("#ctd-color-sizes").val() || "{}")
    } catch (e) {
      console.error("Error parsing color sizes:", e)
    }

    // Get inventory data if available
    const inventoryEnabled = $("#ctd-inventory-enabled").val() === "1"
    let inventory = {}

    try {
      inventory = JSON.parse($("#ctd-inventory").val() || "{}")
      console.log("Loaded inventory data:", inventory)
    } catch (e) {
      console.error("Error parsing inventory:", e)
    }

    // Use color-specific sizes if available, otherwise use default sizes
    if (colorSizes[color] && colorSizes[color].length > 0) {
      sizes = colorSizes[color]
    } else {
      // Get sizes from the product configuration
      $(".ctd-size-table")
        .first()
        .find("th")
        .not(":first")
        .each(function () {
          sizes.push($(this).text())
        })

      // If no sizes found, use default sizes
      if (sizes.length === 0) {
        sizes = ["XS", "S", "M", "L", "XL", "2XL", "3XL", "4XL", "5XL"]
      }
    }

    // Add size headers
    sizes.forEach((size) => {
      headerRow.append("<th>" + size + "</th>")
    })

    table.append($("<thead></thead>").append(headerRow))

    // Add quantity row
    const quantityRow = $("<tr></tr>")
    quantityRow.append(
      "<td>" +
        (typeof ctd_params !== "undefined" && ctd_params.i18n && ctd_params.i18n.quantity
          ? ctd_params.i18n.quantity
          : "Quantity") +
        "</td>",
    )

    sizes.forEach((size) => {
      // Get saved quantity if available
      let quantity = 0
      if (designData.quantities[color] && designData.quantities[color][size]) {
        quantity = designData.quantities[color][size]
      }

      // Check inventory if enabled
      let maxQty = ""
      let inventoryMessage = ""
      let inStock = -1 // Default to unlimited

      if (inventoryEnabled && inventory[color] && inventory[color][size] !== undefined) {
        inStock = Number.parseInt(inventory[color][size])
        maxQty = inStock > 0 ? ' max="' + inStock + '"' : ""

        if (inStock <= 0) {
          inventoryMessage = '<span class="ctd-out-of-stock">Out of stock</span>'
        } else if (inStock < 10) {
          inventoryMessage = '<span class="ctd-low-stock">Only ' + inStock + " left</span>"
        }

        console.log(`Inventory for ${color} size ${size}: ${inStock}`)
      }

      const disabledAttr =
        inventoryEnabled &&
        inventory[color] &&
        inventory[color][size] !== undefined &&
        Number.parseInt(inventory[color][size]) <= 0
          ? ' disabled="disabled"'
          : ""

      quantityRow.append(
        '<td><input type="text" name="ctd_quantity[' +
          color +
          "][" +
          size +
          ']" pattern="[0-9]*" inputmode="numeric"' +
          ' value="' +
          quantity +
          '" class="ctd-quantity-input"' +
          disabledAttr +
          ">" +
          inventoryMessage +
          "</td>",
      )
    })

    table.append($("<tbody></tbody>").append(quantityRow))
    tableWrapper.append(table)

    return tableWrapper
  }

  // Update total quantity and calculate costs
  function updateTotalQuantity() {
    let totalQuantity = 0
    designData.quantities = {}

    // Get quantities from inputs
    $(".ctd-quantity-input").each(function () {
      const input = $(this)
      const name = input.attr("name")
      const inputValue = input.val().trim()
      const quantity = inputValue === "" ? 0 : Number.parseInt(inputValue) || 0

      // Parse name to get color and size
      const matches = name.match(/ctd_quantity\[([^\]]+)\]\[([^\]]+)\]/)
      if (matches && matches.length === 3) {
        const color = matches[1]
        const size = matches[2]

        // Initialize color in quantities object if not exists
        if (!designData.quantities[color]) {
          designData.quantities[color] = {}
        }

        // Add quantity
        designData.quantities[color][size] = quantity
        totalQuantity += quantity
      }
    })

    // Update total quantity display
    $("#ctd-total-quantity").text(totalQuantity)

    // Show warning if below minimum quantity
    const minQuantityNotice = $(".ctd-min-quantity-notice")
    if (totalQuantity < minQuantity) {
      minQuantityNotice.addClass("ctd-validation-error")
    } else {
      minQuantityNotice.removeClass("ctd-validation-error")
    }

    // Update next tier message
    updateNextTierMessage(totalQuantity)

    // Calculate and update costs
    updateCosts(totalQuantity)

    // Update hidden field
    updateDesignData()

    // Debug output
    console.log("Updated quantities:", designData.quantities)
    console.log("Total quantity:", totalQuantity)
  }

  // Update next tier message
  function updateNextTierMessage(totalQuantity) {
    const nextTierMessage = $("#ctd-next-tier-message")
    nextTierMessage.empty()

    if (tierPricing.length === 0 || totalQuantity === 0) {
      return
    }

    // Find current tier and next tier
    let currentTier = null
    let nextTier = null

    // Find current tier
    for (let i = tierPricing.length - 1; i >= 0; i--) {
      const tier = tierPricing[i]
      if (totalQuantity >= tier.min && (tier.max === 0 || totalQuantity <= tier.max)) {
        currentTier = tier
        break
      }
    }

    // Find next tier
    if (currentTier) {
      const currentIndex = tierPricing.indexOf(currentTier)
      if (currentIndex < tierPricing.length - 1) {
        nextTier = tierPricing[currentIndex + 1]
      }
    } else {
      // If no current tier, find the first tier
      for (let i = 0; i < tierPricing.length; i++) {
        if (totalQuantity < tierPricing[i].min) {
          nextTier = tierPricing[i]
          break
        }
      }
    }

    // Display message if next tier exists
    if (nextTier) {
      const itemsNeeded = nextTier.min - totalQuantity
      if (itemsNeeded > 0) {
        // Calculate savings
        const currentDiscount = currentTier ? currentTier.discount : 0
        const nextDiscount = nextTier.discount
        const additionalDiscount = nextDiscount - currentDiscount

        if (additionalDiscount > 0) {
          const savings = (productPrice * totalQuantity * additionalDiscount) / 100
          nextTierMessage.text(
            `Order ${itemsNeeded} more to save ${additionalDiscount}% (approximately $${savings.toFixed(2)})`,
          )
        }
      }
    }
  }

  // Calculate and update costs
  function updateCosts(totalQuantity) {
    // Calculate product cost
    const productTotal = productPrice * totalQuantity

    // Calculate setup fee based on decoration method and number of positions
    let setupFeeTotal = 0
    if (designData.decoration_method && setupFees[designData.decoration_method]) {
      // Multiply setup fee by the number of positions with designs
      setupFeeTotal =
        Number.parseFloat(setupFees[designData.decoration_method]) * Math.max(1, designData.positions.length)
    } else if (designData.positions.length > 0) {
      // If no decoration method selected but designs exist, use default setup fee
      const defaultSetupFee = 10.95 // Default setup fee
      setupFeeTotal = defaultSetupFee * designData.positions.length
    }

    // Calculate discount
    let discountAmount = 0
    let discountPercent = 0

    if (totalQuantity > 0 && tierPricing.length > 0) {
      // Sort tier pricing by min quantity in descending order to find the highest applicable discount
      const sortedTiers = [...tierPricing].sort((a, b) => b.min - a.min)

      // Find applicable discount
      for (const tier of sortedTiers) {
        if (totalQuantity >= tier.min && (tier.max === 0 || totalQuantity <= tier.max)) {
          discountPercent = Number.parseFloat(tier.discount)
          discountAmount = (productTotal * discountPercent) / 100
          break
        }
      }
    }

    // Calculate total cost
    const totalCost = productTotal + setupFeeTotal - discountAmount

    // Update cost displays
    $("#ctd-total-cost").text("$" + totalCost.toFixed(2))
    $("#ctd-product-cost-breakdown").text(`Product cost: $${productTotal.toFixed(2)}`)
    $("#ctd-setup-fee-breakdown").text(`Setup fees: $${setupFeeTotal.toFixed(2)}`)

    if (discountAmount > 0) {
      $("#ctd-discount-breakdown").text(`Discount (${discountPercent}%): -$${discountAmount.toFixed(2)}`)
      $("#ctd-discount-breakdown").show()
    } else {
      $("#ctd-discount-breakdown").hide()
    }

    // Update hidden fields
    $("#ctd-setup-fee").val(setupFeeTotal)
  }

  // Position tabs
  const positionTabs = $(".ctd-position-tab")

  // Handle position tab click
  positionTabs.on("click", function () {
    const position = $(this).data("position")
    currentPosition = position

    // Update active tab
    positionTabs.removeClass("active")
    $(this).addClass("active")

    // Update WooCommerce image
    updateWooCommerceImage(position)
  })

  // Setup WooCommerce overlay
  function setupWooCommerceOverlay() {
    // Find all WooCommerce product gallery images
    const wooGalleryImages = $(".woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image")

    if (wooGalleryImages.length) {
      console.log(`Found ${wooGalleryImages.length} WooCommerce gallery images`)

      // Add overlay to each gallery image
      wooGalleryImages.each(function (index) {
        const galleryImage = $(this)

        // Add overlay div if it doesn't exist
        if (galleryImage.find(".ctd-woo-design-overlay").length === 0) {
          galleryImage.append('<div class="ctd-woo-design-overlay" data-position-index="' + index + '"></div>')
          console.log(`Added design overlay to gallery image ${index}`)
        }
      })

      // Add floating controls to the first gallery image
      const floatingControls = $("#ctd-floating-controls")
      wooGalleryImages.first().append(floatingControls)
      console.log("Added floating controls to first gallery image")

      // Initialize with the current position
      updateWooCommerceImage(currentPosition)
    } else {
      console.log("WooCommerce gallery not found")

      // Try alternative selector
      const altGallery = $(".woocommerce-product-gallery__image")
      if (altGallery.length) {
        console.log("Found alternative gallery element")

        // Add overlay div if it doesn't exist
        if (altGallery.find(".ctd-woo-design-overlay").length === 0) {
          altGallery.append('<div class="ctd-woo-design-overlay"></div>')
          console.log("Added design overlay to alternative gallery")

          // Add floating controls to the gallery
          const floatingControls = $("#ctd-floating-controls")
          altGallery.append(floatingControls)
          console.log("Added floating controls to alternative gallery")
        }

        // Initialize with the current position
        updateWooCommerceImage(currentPosition)
      } else {
        console.log("No suitable gallery element found")

        // As a fallback, try to find any product image
        const productImg = $(".woocommerce-product-gallery img:first-child")
        if (productImg.length) {
          console.log("Found product image as fallback")

          // Wrap the image in a relative positioned div if not already
          if (!productImg.parent().hasClass("ctd-image-wrapper")) {
            productImg.wrap('<div class="ctd-image-wrapper" style="position:relative;"></div>')
          }

          const wrapper = productImg.parent()

          // Add overlay div if it doesn't exist
          if (wrapper.find(".ctd-woo-design-overlay").length === 0) {
            wrapper.append('<div class="ctd-woo-design-overlay"></div>')
            console.log("Added design overlay to fallback image")

            // Add floating controls to the wrapper
            const floatingControls = $("#ctd-floating-controls")
            wrapper.append(floatingControls)
            console.log("Added floating controls to fallback image")
          }

          // Initialize with the current position
          updateWooCommerceImage(currentPosition)
        }
      }
    }
  }

  // Update WooCommerce image based on position
  function updateWooCommerceImage(position) {
    console.log("Updating WooCommerce image for position:", position)

    // Map positions to gallery image indices
    const positionIndex = {
      front: 0, // First image
      back: 1, // Second image
      side: 2, // Third image
    }

    // Get the index for the current position
    const imageIndex = positionIndex[position] || 0
    console.log(`Position ${position} maps to image index ${imageIndex}`)

    // Find all gallery images
    const galleryImages = $(".woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image")

    // Clear all overlays first
    galleryImages.find(".ctd-woo-design-overlay").empty()

    // Hide floating controls initially
    $("#ctd-floating-controls").removeClass("active")

    // If we have enough gallery images, use the one at the specified index
    if (galleryImages.length > imageIndex) {
      const targetGallery = galleryImages.eq(imageIndex)
      console.log(`Found gallery image at index ${imageIndex} for position ${position}`)

      // Get the overlay for this gallery image
      const overlay = targetGallery.find(".ctd-woo-design-overlay")

      // If there's a design for this position, show it
      if (designData.designs[position]) {
        console.log("Found design for position:", position, designData.designs[position])

        // Show floating controls and move them to the current gallery image
        const floatingControls = $("#ctd-floating-controls")
        targetGallery.append(floatingControls)
        floatingControls.addClass("active")
        console.log("Moved and activated floating controls")

        // Get position data
        const posData = designData.design_positions[position] || { x: 50, y: 50, scale: 1, rotation: 0 }
        console.log("Position data:", posData)

        // Create image element with proper transform
        const img = $(
          '<img src="' + designData.designs[position] + '" alt="Design Overlay" class="ctd-draggable-design">',
        )

        // Add error handling for the image
        img.on("error", () => {
          console.error("Failed to load design image:", designData.designs[position])
          showValidationMessage(
            "The design image could not be loaded. It may have been deleted or moved. Please try uploading again.",
            "error",
          )

          // Remove the design from designData
          delete designData.designs[position]

          // Remove position from list
          const posIndex = designData.positions.indexOf(position)
          if (posIndex !== -1) {
            designData.positions.splice(posIndex, 1)
          }

          // Update design data
          updateDesignData()

          // Hide floating controls
          floatingControls.removeClass("active")

          // Clear overlay
          overlay.empty()
        })

        // Apply transforms in the correct order: first translate, then rotate, then scale
        const transform = `translate(-50%, -50%) rotate(${posData.rotation || 0}deg) scale(${posData.scale})`

        img.css({
          top: posData.y + "%",
          left: posData.x + "%",
          transform: transform,
        })

        console.log("Created design image with CSS:", {
          top: posData.y + "%",
          left: posData.x + "%",
          transform: transform,
        })

        // Add to overlay
        overlay.append(img)
        console.log("Added design image to overlay")

        // Make design draggable
        makeDesignDraggable(img, position)
        console.log("Made design draggable")
      } else {
        console.log("No design found for position:", position)
      }
    } else {
      console.log(`Not enough gallery images (${galleryImages.length}) for position index ${imageIndex}`)
    }
  }

  // Handle decoration method selection
  $("#ctd-decoration-method").on("change", function () {
    const method = $(this).val()
    const setupFeeInfo = $("#ctd-setup-fee-info")

    // Update design data
    designData.decoration_method = method

    // Update setup fee display
    if (method && setupFees[method] !== undefined) {
      const setupFee = Number.parseFloat(setupFees[method])
      if (setupFee > 0) {
        setupFeeInfo.html(`<p>Setup fee: $${setupFee.toFixed(2)} per position</p>`)
      } else {
        setupFeeInfo.html(`<p>No setup fee</p>`)
      }
    } else {
      setupFeeInfo.html(`<p></p>`)
    }

    // Update costs
    updateCosts(Number.parseInt($("#ctd-total-quantity").text()) || 0)

    // Update hidden field
    updateDesignData()
  })

  // Upload squares
  const uploadSquares = $(".ctd-upload-square")

  // Handle upload square click - simplified approach
  uploadSquares.each(function () {
    const square = $(this)
    const fileInput = square.find("input[type='file']")

    // Make the file input accessible but invisible
    fileInput.css({
      position: "absolute",
      opacity: "0",
      width: "1px",
      height: "1px",
    })

    // Use a direct click handler on specific elements
    square.find(".ctd-upload-square-label, .ctd-upload-square-icon, .ctd-upload-square-text").on("click", (e) => {
      e.preventDefault()
      e.stopPropagation()
      console.log("Upload element clicked - opening file dialog")
      fileInput[0].click() // Use native click instead of jQuery trigger
    })
  })

  // Handle file input change - simplified
  $(".ctd-design-upload").on("change", function (e) {
    console.log("File input changed")
    if (this.files && this.files.length > 0) {
      console.log("File selected:", this.files[0].name)
      handleFileUpload(this)
    }
  })

  // Handle drag and drop on upload squares with improved implementation
  uploadSquares.each(function () {
    const square = $(this)
    const input = square.find("input[type='file']")
    const position = square.data("position")

    // Prevent default on all drag events to allow dropping
    square.on("dragenter dragover dragleave drop", (e) => {
      e.preventDefault()
      e.stopPropagation()
      return false
    })

    // Visual feedback when dragging over
    square.on("dragenter dragover", function () {
      console.log("Drag enter/over on position:", position)
      $(this).addClass("active")
    })

    square.on("dragleave", function () {
      console.log("Drag leave on position:", position)
      $(this).removeClass("active")
    })

    // Handle the actual drop
    square.on("drop", function (e) {
      console.log("Drop event on position:", position)
      $(this).removeClass("active")

      // Get dropped files
      const dt = e.originalEvent.dataTransfer
      if (dt && dt.files && dt.files.length > 0) {
        console.log("Files dropped:", dt.files.length)

        // Update the file input with the dropped file
        input[0].files = dt.files

        // Process the file
        handleFileUpload(input[0])
      } else {
        console.log("No files in drop event")
      }
    })
  })

  // Handle file upload with improved error handling
  function handleFileUpload(inputElement) {
    const input = $(inputElement)
    const position = input.data("position")

    if (!inputElement.files || inputElement.files.length === 0) {
      console.log("No file selected in handleFileUpload")
      return
    }

    const file = inputElement.files[0]
    const uploadSquare = input.closest(".ctd-upload-square")

    console.log("Processing file for upload:", file.name, "Position:", position)

    // Check file type
    const allowedTypes = ["image/jpeg", "image/png", "image/gif"]
    if (allowedTypes.indexOf(file.type) === -1) {
      showValidationMessage("Invalid file type. Only JPG, PNG, and GIF files are allowed.", "error")
      return
    }

    // Check file size
    if (file.size > 5000000) {
      showValidationMessage("File size should be less than 5MB", "error")
      return
    }

    // Show loading state
    uploadSquare.addClass("loading")
    const previewContainer = uploadSquare.find(".ctd-upload-square-preview")
    previewContainer.html("<div class='ctd-loading'>Uploading...</div>").show()

    console.log("Creating local preview...")

    // Create a simple preview immediately
    const reader = new FileReader()
    reader.onload = (e) => {
      console.log("Local preview created")

      // Store design data temporarily
      designData.designs[position] = e.target.result

      // Initialize design position data
      if (!designData.design_positions[position]) {
        designData.design_positions[position] = { x: 50, y: 50, scale: 1, rotation: 0 }
      }

      // Add position to list if not already added
      if (designData.positions.indexOf(position) === -1) {
        designData.positions.push(position)
      }

      // Update current position
      currentPosition = position

      // Show preview in upload square - simplified
      previewContainer
        .html("")
        .append(
          $('<img src="' + e.target.result + '" alt="Design Preview">'),
          $('<span class="ctd-upload-square-remove">×</span>'),
        )
        .show()

      // Update WooCommerce image
      updateWooCommerceImage(position)

      // Update setup fees
      updateSetupFees()

      // Update costs
      updateCosts(Number.parseInt($("#ctd-total-quantity").text()) || 0)

      // Update hidden field
      updateDesignData()

      // Remove loading state if we're just using local preview
      uploadSquare.removeClass("loading")
    }

    reader.onerror = () => {
      console.error("FileReader error")
      uploadSquare.removeClass("loading")
      showValidationMessage("Error creating preview. Please try again.", "error")
    }

    // Start reading the file
    reader.readAsDataURL(file)

    // Create FormData for server upload
    const formData = new FormData()
    formData.append("action", "ctd_upload_design")
    formData.append("nonce", typeof ctd_params !== "undefined" ? ctd_params.nonce : "")
    formData.append("position", position)
    formData.append("design", file)
    formData.append("product_id", productId)

    console.log("Uploading file to server...")

    // Upload file to server
    $.ajax({
      url: typeof ctd_params !== "undefined" ? ctd_params.ajax_url : "/wp-admin/admin-ajax.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: (response) => {
        console.log("Upload response:", response)

        if (response && response.success) {
          console.log("Upload successful:", response.data.file_url)

          // Update with server URL
          designData.designs[position] = response.data.file_url

          // Update setup fees
          updateSetupFees()

          // Update costs
          updateCosts(Number.parseInt($("#ctd-total-quantity").text()) || 0)

          // Update hidden field
          updateDesignData()

          showValidationMessage("Design uploaded successfully!", "success")
        } else {
          console.log("Upload failed:", response ? response.data.message : "Unknown error")
          showValidationMessage("Server upload failed, using local preview", "warning")
        }
      },
      error: (xhr, status, error) => {
        console.log("Upload error:", status, error)
        showValidationMessage("Server upload error. Using local preview.", "warning")
      },
      complete: () => {
        // Always remove loading state when ajax completes
        uploadSquare.removeClass("loading")
      },
    })
  }

  // Handle remove design - simplified
  $(document).on("click", ".ctd-upload-square-remove", function (e) {
    e.preventDefault()
    e.stopPropagation()

    const uploadSquare = $(this).closest(".ctd-upload-square")
    const position = uploadSquare.data("position")

    // Reset the file input by cloning and replacing it
    const oldInput = uploadSquare.find("input[type='file']")
    const newInput = oldInput.clone(true).val("")
    oldInput.replaceWith(newInput)

    // Hide and clear preview
    uploadSquare.find(".ctd-upload-square-preview").empty().hide()

    // Show the upload elements again
    uploadSquare.find(".ctd-upload-square-label, .ctd-upload-square-icon, .ctd-upload-square-text").show()

    // Remove design data
    if (designData.designs[position]) {
      delete designData.designs[position]
    }

    // Remove position from list
    const posIndex = designData.positions.indexOf(position)
    if (posIndex !== -1) {
      designData.positions.splice(posIndex, 1)
    }

    // Update WooCommerce image
    updateWooCommerceImage(position)

    // Update setup fees and costs
    updateSetupFees()
    updateCosts(Number.parseInt($("#ctd-total-quantity").text()) || 0)

    // Update hidden field
    updateDesignData()
  })

  // Make design draggable
  function makeDesignDraggable(element, position) {
    let isDragging = false
    let startX, startY, startLeft, startTop

    // Get the container (overlay)
    const container = element.parent()

    // Store container dimensions
    const containerWidth = container.width()
    const containerHeight = container.height()

    console.log("Making design draggable. Container dimensions:", containerWidth, "x", containerHeight)

    // Remove any existing event handlers to prevent duplicates
    element.off("mousedown touchstart")

    // Add mouse and touch event handlers
    element.on("mousedown touchstart", (e) => {
      e.preventDefault()
      e.stopPropagation()
      isDragging = true

      // Get event coordinates
      const pageX = e.type === "touchstart" ? e.originalEvent.touches[0].pageX : e.pageX
      const pageY = e.type === "touchstart" ? e.originalEvent.touches[0].pageY : e.pageY

      startX = pageX
      startY = pageY

      // Get element position
      const rect = element[0].getBoundingClientRect()
      startLeft = rect.left + rect.width / 2
      startTop = rect.top + rect.height / 2

      // Add move and end events to document
      $(document).on("mousemove touchmove", moveHandler)
      $(document).on("mouseup touchend", endHandler)
    })

    // Move handler
    function moveHandler(e) {
      if (!isDragging) return

      // Get event coordinates
      const pageX = e.type === "touchmove" ? e.originalEvent.touches[0].pageX : e.pageX
      const pageY = e.type === "touchmove" ? e.originalEvent.touches[0].pageY : e.pageY

      const dx = pageX - startX
      const dy = pageY - startY

      // Get container position
      const containerRect = container[0].getBoundingClientRect()

      // Calculate new position as percentage of container
      const newX = ((startLeft + dx - containerRect.left) / containerWidth) * 100
      const newY = ((startTop + dy - containerRect.top) / containerHeight) * 100

      console.log("Moving to:", newX, newY)

      // Update element position
      element.css({
        left: newX + "%",
        top: newY + "%",
      })

      // Update design position data
      if (!designData.design_positions[position]) {
        designData.design_positions[position] = { x: 50, y: 50, scale: 1, rotation: 0 }
      }

      designData.design_positions[position].x = newX
      designData.design_positions[position].y = newY

      // Update design data
      updateDesignData()
    }

    // End handler
    function endHandler() {
      isDragging = false
      console.log("Drag ended")

      // Remove move and end events
      $(document).off("mousemove touchmove", moveHandler)
      $(document).off("mouseup touchend", endHandler)
    }
  }

  // Control buttons for design positioning
  $(".ctd-zoom-in").on("click", () => {
    zoomDesign(0.1) // Zoom in by 10%
  })

  $(".ctd-zoom-out").on("click", () => {
    zoomDesign(-0.1) // Zoom out by 10%
  })

  $(".ctd-move-up").on("click", () => {
    moveDesign(0, -5) // Move up by 5%
  })

  $(".ctd-move-down").on("click", () => {
    moveDesign(0, 5) // Move down by 5%
  })

  $(".ctd-move-left").on("click", () => {
    moveDesign(-5, 0) // Move left by 5%
  })

  $(".ctd-move-right").on("click", () => {
    moveDesign(5, 0) // Move right by 5%
  })

  $(".ctd-reset").on("click", () => {
    resetDesignPosition()
  })

  // Zoom design
  function zoomDesign(delta) {
    if (!designData.designs[currentPosition]) return

    // Get current position data
    if (!designData.design_positions[currentPosition]) {
      designData.design_positions[currentPosition] = { x: 50, y: 50, scale: 1 }
    }

    const posData = designData.design_positions[currentPosition]
    const newScale = Math.max(0.2, Math.min(2, posData.scale + delta)) // Limit scale between 0.2 and 2
    posData.scale = newScale

    // Update WooCommerce image
    updateWooCommerceImage(currentPosition)

    // Update design data
    updateDesignData()
  }

  // Move design
  function moveDesign(deltaX, deltaY) {
    if (!designData.designs[currentPosition]) return

    // Get current position data
    if (!designData.design_positions[currentPosition]) {
      designData.design_positions[currentPosition] = { x: 50, y: 50, scale: 1 }
    }

    const posData = designData.design_positions[currentPosition]

    // Calculate new position (constrain within 10-90% range)
    const newX = Math.max(10, Math.min(90, posData.x + deltaX))
    const newY = Math.max(10, Math.min(90, posData.y + deltaY))

    posData.x = newX
    posData.y = newY

    // Update WooCommerce image
    updateWooCommerceImage(currentPosition)

    // Update design data
    updateDesignData()
  }

  // Reset design position
  function resetDesignPosition() {
    if (!designData.designs[currentPosition]) return

    // Reset position data
    designData.design_positions[currentPosition] = { x: 50, y: 50, scale: 1 }

    // Update WooCommerce image
    updateWooCommerceImage(currentPosition)

    // Update design data
    updateDesignData()
  }

  // Update setup fees
  function updateSetupFees() {
    // Update costs
    updateCosts(Number.parseInt($("#ctd-total-quantity").text()) || 0)

    // Update positions hidden field
    $("#ctd-positions").val(designData.positions.join(","))
  }

  // Generate composite images before form submission
  $("form.cart").on("submit", function (e) {
    // Check if designer is enabled and has designs
    if (designer.length && designData.positions.length > 0) {
      e.preventDefault()

      // Generate composite images for all positions
      const compositePromises = []
      const compositeImages = {}

      designData.positions.forEach((position) => {
        const promise = generateCompositeImage(position).then((imageData) => {
          if (imageData) {
            compositeImages[position] = imageData
          }
        })
        compositePromises.push(promise)
      })

      // When all composites are generated, submit the form
      Promise.all(compositePromises).then(() => {
        // Store composite images in hidden field
        $("#ctd-composite-images").val(JSON.stringify(compositeImages))

        // Submit the form
        $(this).off("submit").submit()
      })
    }
  })

  // Generate composite image
  function generateCompositeImage(position) {
    return new Promise((resolve) => {
      if (!designData.designs[position]) {
        resolve(null)
        return
      }

      // Get position data
      const posData = designData.design_positions[position] || { x: 50, y: 50, scale: 1 }

      // Create canvas
      const canvas = document.createElement("canvas")
      const ctx = canvas.getContext("2d")

      // Get product image based on position
      let imageUrl
      if (position === "front") {
        imageUrl = frontImage
      } else if (position === "back") {
        imageUrl = backImage
      } else {
        imageUrl = sideImage
      }

      // Load product image
      const productImg = new Image()
      productImg.crossOrigin = "anonymous"
      productImg.onload = () => {
        // Set canvas size to match product image
        canvas.width = productImg.width
        canvas.height = productImg.height

        // Draw product image
        ctx.drawImage(productImg, 0, 0, canvas.width, canvas.height)

        // Load design image
        const designImg = new Image()
        designImg.crossOrigin = "anonymous"
        designImg.onload = () => {
          // Calculate design position and size
          const designWidth = designImg.width * posData.scale
          const designHeight = designImg.height * posData.scale
          const x = (canvas.width * posData.x) / 100 - designWidth / 2
          const y = (canvas.height * posData.y) / 100 - designHeight / 2

          // Draw design image
          ctx.drawImage(designImg, x, y, designWidth, designHeight)

          // Get image data
          const imageData = canvas.toDataURL("image/png")

          // Save composite image
          saveCompositeImage(imageData, position).then((response) => {
            if (response && response.success) {
              resolve(response.data.file_url)
            } else {
              resolve(null)
            }
          })
        }
        designImg.src = designData.designs[position]
      }
      productImg.src = imageUrl
    })
  }

  // Save composite image
  function saveCompositeImage(imageData, position) {
    return new Promise((resolve) => {
      $.ajax({
        url: typeof ctd_params !== "undefined" ? ctd_params.ajax_url : "/wp-admin/admin-ajax.php",
        type: "POST",
        data: {
          action: "ctd_save_composite",
          nonce: typeof ctd_params !== "undefined" ? ctd_params.nonce : "",
          image_data: imageData,
          position: position,
          product_id: productId,
        },
        success: (response) => {
          resolve(response)
        },
        error: () => {
          resolve(null)
        },
      })
    })
  }

  // Update design data hidden field
  function updateDesignData() {
    $("#ctd-design-data").val(JSON.stringify(designData))
  }

  // Show validation message with support for success messages
  function showValidationMessage(message, type) {
    const messagesContainer = $(".ctd-validation-messages")
    const messageElement = $('<div class="ctd-validation-message ctd-validation-' + type + '">' + message + "</div>")

    messagesContainer.append(messageElement)

    // Remove message after 5 seconds
    setTimeout(() => {
      messageElement.fadeOut(function () {
        $(this).remove()
      })
    }, 5000)
  }

  // Decoration methods help modal
  const decorationHelpLink = $("#ctd-decoration-help-link")
  const decorationHelpModal = $("#ctd-decoration-help-modal")
  const modalClose = $(".ctd-modal-close")

  decorationHelpLink.on("click", (e) => {
    e.preventDefault()
    decorationHelpModal.css("display", "block")
  })

  modalClose.on("click", () => {
    decorationHelpModal.css("display", "none")
  })

  $(window).on("click", (e) => {
    if ($(e.target).is(decorationHelpModal)) {
      decorationHelpModal.css("display", "none")
    }
  })

  // Initialize
  updateSizeTables()
  updateSetupFees()

  // Initialize on document ready
  $(document).ready(() => {
    console.log("Document ready, initializing WooCommerce overlay")
    setupWooCommerceOverlay()

    // Ensure overlay is set up after WooCommerce gallery is fully loaded
    setTimeout(() => {
      console.log("Delayed initialization of WooCommerce overlay")
      setupWooCommerceOverlay()
    }, 1000)
  })

  // Add rotation functionality
  $(".ctd-rotate-left").on("click", () => {
    rotateDesign(-15) // Rotate left by 15 degrees
  })

  $(".ctd-rotate-right").on("click", () => {
    rotateDesign(15) // Rotate right by 15 degrees
  })

  // Rotation function
  function rotateDesign(delta) {
    if (!designData.designs[currentPosition]) return

    // Get current position data
    if (!designData.design_positions[currentPosition]) {
      designData.design_positions[currentPosition] = { x: 50, y: 50, scale: 1, rotation: 0 }
    }

    const posData = designData.design_positions[currentPosition]
    const currentRotation = posData.rotation || 0
    posData.rotation = currentRotation + delta

    // Update WooCommerce image
    updateWooCommerceImage(currentPosition)

    // Update design data
    updateDesignData()
  }
})
