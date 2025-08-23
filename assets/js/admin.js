/**
 * Custom T-Shirt Designer Admin Scripts
 */
jQuery(document).ready(($) => {
  // Initialize color picker for existing color inputs
  initColorPickers()

  function initColorPickers() {
    $(".ctd-color-key").each(function () {
      if (!$(this).hasClass("wp-color-picker")) {
        $(this).wpColorPicker({
          change: function (event, ui) {
            // Update the color preview when color changes
            $(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color", ui.color.toString())

            // Update the color key value
            $(this).val(ui.color.toString())

            // Ensure the form is marked as changed
            $("form#post").trigger("change")
          },
          clear: function () {
            // Update the color preview when color is cleared
            $(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color", "")
          },
        })
      }
    })
  }

  // Add color button functionality
  $("#ctd_add_color").on("click", () => {
    // Generate a unique temporary ID for the new color
    const tempId = "new_color_" + Math.floor(Math.random() * 10000)

    // Updated default sizes to include 4XL and 5XL
    const defaultSizes = ["2XS", "XS", "S", "M", "L", "XL", "2XL", "3XL", "4XL", "5XL"]

    // Create size options HTML
    let sizeOptionsHtml = ""
    defaultSizes.forEach((size) => {
      sizeOptionsHtml += `
        <div class="ctd-color-size-item">
          <label>
            <input type="checkbox" name="_ctd_color_sizes[${tempId}][]" value="${size}" checked> 
            ${size}
          </label>
          <div class="ctd-size-inventory">
            <input type="text" name="_ctd_inventory[${tempId}][${size}]" placeholder="Qty" value="0" min="0" class="ctd-inventory-input">
          </div>
        </div>
      `
    })

    // Create the color row HTML
    const colorRowHtml = `
      <div class="ctd-color-row">
        <div class="ctd-color-header">
          <input type="text" name="_ctd_color_keys[]" value="#000000" placeholder="Color code (e.g. #FF0000 or red)" class="ctd-color-key">
          <input type="text" name="_ctd_color_names[]" value="" placeholder="Color name" class="ctd-color-name">
          <span class="ctd-color-preview" style="background-color: #000000;"></span>
          <a href="#" class="ctd-remove-color button">Remove</a>
        </div>
        
        <!-- Size options for this color -->
        <div class="ctd-color-sizes">
          <h4>Sizes for this color</h4>
          <div class="ctd-color-size-options">
            ${sizeOptionsHtml}
          </div>
          <div class="ctd-add-custom-size-container">
            <input type="text" class="ctd-custom-size-input" placeholder="Custom size (e.g. 6XL)">
            <button type="button" class="button ctd-add-custom-size" data-color="${tempId}">Add Size</button>
          </div>
        </div>
      </div>
    `

    // Append to colors container
    $("#ctd_colors_container").append(colorRowHtml)

    // Initialize color picker for the new row
    const newRow = $("#ctd_colors_container .ctd-color-row").last()
    newRow.find(".ctd-color-key").wpColorPicker({
      change: function (event, ui) {
        $(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color", ui.color.toString())

        // Update the color key value
        $(this).val(ui.color.toString())

        // Ensure the form is marked as changed
        $("form#post").trigger("change")
      },
      clear: function () {
        // Update the color preview when color is cleared
        $(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color", "")
      },
    })

    // Update the color key when it changes
    const colorKey = newRow.find(".ctd-color-key")
    colorKey.on("change", function () {
      const newColorKey = $(this).val().trim()
      if (newColorKey) {
        const inputs = newRow.find(`input[name*='[${tempId}]']`)
        inputs.each(function () {
          const name = $(this).attr("name")
          $(this).attr("name", name.replace(tempId, newColorKey))
        })

        newRow.find(".ctd-add-custom-size").attr("data-color", newColorKey)
      }
    })

    // Ensure the form is marked as changed
    $("form#post").trigger("change")
  })

  // Remove color
  $(document).on("click", ".ctd-remove-color", function (e) {
    e.preventDefault()
    $(this).closest(".ctd-color-row").remove()

    // Ensure the form is marked as changed
    $("form#post").trigger("change")
  })

  // Update color preview on input
  $(document).on("input", ".ctd-color-key", function () {
    var colorValue = $(this).val()
    $(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color", colorValue)
  })

  // Add custom size for a specific color
  $(document).on("click", ".ctd-add-custom-size", function (e) {
    e.preventDefault()
    const button = $(this)
    const colorKey = button.data("color")
    const container = button.closest(".ctd-color-sizes").find(".ctd-color-size-options")
    const input = button.closest(".ctd-add-custom-size-container").find(".ctd-custom-size-input")
    const customSize = input.val().trim()

    if (customSize) {
      // Add to custom sizes list with proper styling and structure
      container.append(`
        <div class="ctd-color-size-item ctd-custom-size-item">
          <label>
            <input type="checkbox" name="_ctd_color_sizes[${colorKey}][]" value="${customSize}" checked="checked"> 
            ${customSize}
          </label>
          <div class="ctd-size-inventory">
            <input type="text" name="_ctd_inventory[${colorKey}][${customSize}]" placeholder="Qty" value="0" min="0" class="ctd-inventory-input">
          </div>
          <a href="#" class="ctd-remove-custom-size">×</a>
        </div>
      `)

      // Clear input
      input.val("")

      // Ensure the form is marked as changed
      $("form#post").trigger("change")
    }
  })

  // Remove custom size for a specific color
  $(document).on("click", ".ctd-remove-custom-size", function (e) {
    e.preventDefault()
    $(this).closest(".ctd-color-size-item").remove()

    // Ensure the form is marked as changed
    $("form#post").trigger("change")
  })

  // Add decoration method
  $("#ctd_add_decoration_method").on("click", () => {
    var methodRow = `
      <div class="ctd-decoration-method-row">
        <input type="text" name="_ctd_decoration_method_keys[]" placeholder="Method key (e.g. screen_printing)" class="ctd-decoration-method-key">
        <input type="text" name="_ctd_decoration_method_names[]" placeholder="Method name (e.g. Screen Printing)" class="ctd-decoration-method-name">
        <div class="ctd-decoration-method-fee">
          <label>Setup Fee ($):</label>
          <input type="number" name="_ctd_decoration_method_fees[]" value="0.00" step="0.01" min="0">
        </div>
        <div class="ctd-decoration-method-actions">
          <button type="button" class="button ctd-remove-decoration-method">Remove</button>
        </div>
      </div>
    `
    $("#ctd_decoration_methods_container").append(methodRow)

    // Ensure the form is marked as changed
    $("form#post").trigger("change")
  })

  // Remove decoration method
  $(document).on("click", ".ctd-remove-decoration-method", function () {
    $(this).closest(".ctd-decoration-method-row").remove()

    // Ensure the form is marked as changed
    $("form#post").trigger("change")
  })

  // Add pricing tier
  $("#ctd_add_tier").on("click", () => {
    var tierRow = `
      <tr class="ctd-tier-row">
        <td><input type="number" name="_ctd_tier_min[]" value="" min="0"></td>
        <td><input type="number" name="_ctd_tier_max[]" value="" min="0" placeholder="0 = no limit"></td>
        <td><input type="number" name="_ctd_tier_discount[]" value="" min="0" max="100" step="0.1"></td>
        <td><button type="button" class="button ctd-remove-tier">Remove</button></td>
      </tr>
    `
    $("#ctd-tier-pricing-table tbody").append(tierRow)

    // Ensure the form is marked as changed
    $("form#post").trigger("change")
  })

  // Remove pricing tier
  $(document).on("click", ".ctd-remove-tier", function () {
    $(this).closest("tr").remove()

    // Ensure the form is marked as changed
    $("form#post").trigger("change")
  })

  // Ensure inventory inputs are properly tracked
  $(document).on("input change", ".ctd-inventory-input", () => {
    // Mark the form as changed
    $("form#post").trigger("change")
  })

  // Make sure checkboxes trigger form changes
  $(document).on("change", "input[type='checkbox']", () => {
    // Mark the form as changed
    $("form#post").trigger("change")
  })

  // Add form submission handler to ensure all data is properly saved
  $("form#post").on("submit", () => {
    // Validate that all colors have keys and names
    let valid = true
    $(".ctd-color-row").each(function () {
      const colorKey = $(this).find(".ctd-color-key").val().trim()
      const colorName = $(this).find(".ctd-color-name").val().trim()

      if (!colorKey || !colorName) {
        valid = false
        $(this).find(".ctd-color-key, .ctd-color-name").css("border-color", "red")
      }
    })

    if (!valid) {
      alert("Please fill in all color codes and names before saving.")
      return false
    }

    // Make sure all color pickers have their values properly set
    $(".ctd-color-key.wp-color-picker").each(function () {
      const colorValue = $(this).val()
      if (!colorValue) {
        $(this).val($(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color"))
      }
    })

    return true
  })

  // Initialize color picker for any new inputs that might be added dynamically
  $(document).on("focus", ".ctd-color-key:not(.wp-color-picker)", function () {
    $(this).wpColorPicker({
      change: function (event, ui) {
        $(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color", ui.color.toString())

        // Update the color key value
        $(this).val(ui.color.toString())

        // Ensure the form is marked as changed
        $("form#post").trigger("change")
      },
      clear: function () {
        // Update the color preview when color is cleared
        $(this).closest(".ctd-color-row").find(".ctd-color-preview").css("background-color", "")
      },
    })
  })
})
