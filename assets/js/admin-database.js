jQuery(document).ready(($) => {
  // Check database status
  $("#ctd-check-database").on("click", function () {
    const button = $(this)
    const resultContainer = $("#ctd-database-result")
    const statusTable = $("#ctd-database-status-table tbody")

    // Disable button
    button.prop("disabled", true).text("Checking...")

    // Clear result
    resultContainer.removeClass("success error").empty().hide()

    // Show loading in table
    statusTable.html('<tr><td colspan="4">Checking database tables...</td></tr>')

    // Send AJAX request
    $.ajax({
      url: ctd_admin.ajax_url,
      type: "POST",
      data: {
        action: "ctd_check_database_status",
        nonce: ctd_admin.nonce,
      },
      success: (response) => {
        // Enable button
        button.prop("disabled", false).text("Check Database Status")

        if (response.success && response.data.tables) {
          // Clear table
          statusTable.empty()

          // Add table rows
          response.data.tables.forEach((table) => {
            const statusClass = table.exists ? "success" : "error"
            const statusText = table.exists ? "Exists" : "Missing"

            const structureClass = table.structure ? "success" : "error"
            const structureText = table.structure ? "Valid" : "Invalid"

            statusTable.append(`
                            <tr>
                                <td>${table.name}</td>
                                <td><span class="${statusClass}">${statusText}</span></td>
                                <td>${table.exists ? table.records : "N/A"}</td>
                                <td>${table.exists ? `<span class="${structureClass}">${structureText}</span>` : "N/A"}</td>
                            </tr>
                        `)
          })

          // Show summary
          const missingTables = response.data.tables.filter((table) => !table.exists).length
          const invalidStructures = response.data.tables.filter((table) => table.exists && !table.structure).length

          if (missingTables > 0 || invalidStructures > 0) {
            resultContainer
              .addClass("error")
              .html(`
                                <p>Database issues found:</p>
                                <ul>
                                    ${missingTables > 0 ? `<li>${missingTables} missing table(s)</li>` : ""}
                                    ${invalidStructures > 0 ? `<li>${invalidStructures} table(s) with invalid structure</li>` : ""}
                                </ul>
                                <p>Click "Repair Tables" to fix these issues.</p>
                            `)
              .show()
          } else {
            resultContainer
              .addClass("success")
              .html(`
                                <p>All database tables exist and have valid structures.</p>
                            `)
              .show()
          }
        } else {
          statusTable.html('<tr><td colspan="4">Error checking database tables.</td></tr>')
          resultContainer.addClass("error").text("An error occurred while checking database tables.").show()
        }
      },
      error: () => {
        // Enable button
        button.prop("disabled", false).text("Check Database Status")

        // Show error
        statusTable.html('<tr><td colspan="4">Error checking database tables.</td></tr>')
        resultContainer.addClass("error").text("An error occurred while checking database tables.").show()
      },
    })
  })

  // Regenerate tables
  $("#ctd-regenerate-tables").on("click", function () {
    const button = $(this)
    const resultContainer = $("#ctd-database-result")

    // Confirm regeneration
    if (!confirm(ctd_admin.confirm_regenerate)) {
      return
    }

    // Disable button
    button.prop("disabled", true).text("Processing...")

    // Clear result
    resultContainer.removeClass("success error").empty().hide()

    // Send AJAX request
    $.ajax({
      url: ctd_admin.ajax_url,
      type: "POST",
      data: {
        action: "ctd_regenerate_tables",
        nonce: ctd_admin.nonce,
      },
      success: (response) => {
        // Enable button
        button.prop("disabled", false).text("Regenerate Database Tables")

        if (response.success) {
          resultContainer
            .addClass("success")
            .html(`
                            <p>${response.data.message}</p>
                            <p>Please click "Check Database Status" to verify the tables.</p>
                        `)
            .show()
        } else {
          resultContainer.addClass("error").text(response.data.message).show()
        }
      },
      error: () => {
        // Enable button
        button.prop("disabled", false).text("Regenerate Database Tables")

        // Show error
        resultContainer.addClass("error").text("An error occurred. Please try again.").show()
      },
    })
  })

  // Repair tables
  $("#ctd-repair-tables").on("click", function () {
    const button = $(this)
    const resultContainer = $("#ctd-database-result")

    // Confirm repair
    if (!confirm(ctd_admin.confirm_repair)) {
      return
    }

    // Disable button
    button.prop("disabled", true).text("Repairing...")

    // Clear result
    resultContainer.removeClass("success error").empty().hide()

    // Send AJAX request
    $.ajax({
      url: ctd_admin.ajax_url,
      type: "POST",
      data: {
        action: "ctd_repair_tables",
        nonce: ctd_admin.nonce,
      },
      success: (response) => {
        // Enable button
        button.prop("disabled", false).text("Repair Tables")

        if (response.success) {
          resultContainer
            .addClass("success")
            .html(`
                            <p>Database tables have been repaired successfully.</p>
                            <p>Please click "Check Database Status" to verify the tables.</p>
                        `)
            .show()
        } else {
          resultContainer.addClass("error").text(response.data.message).show()
        }
      },
      error: () => {
        // Enable button
        button.prop("disabled", false).text("Repair Tables")

        // Show error
        resultContainer.addClass("error").text("An error occurred while repairing tables. Please try again.").show()
      },
    })
  })

  // Automatically check database status when the page loads
  if ($("#ctd-database-status-table").length) {
    setTimeout(() => {
      $("#ctd-check-database").trigger("click")
    }, 500)
  }
})
