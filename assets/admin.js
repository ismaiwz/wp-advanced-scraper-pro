jQuery(document).ready(($) => {
  // Global variables
  var progressInterval

  // Schedule type change
  $("#schedule_type").on("change", function () {
    if ($(this).val() === "scheduled") {
      $("#schedule_interval_row").slideDown()
    } else {
      $("#schedule_interval_row").slideUp()
    }
  })

  // Test URL
  $("#test-url").on("click", function () {
    var $button = $(this)
    var url = $("#task_url").val()
    var scrapeType = $("#scrape_type").val()

    if (!url) {
      alert(waspAjax.strings.error + ": URL is required")
      return
    }

    $button.prop("disabled", true).addClass("wasp-loading").text(waspAjax.strings.testing_url)

    $.ajax({
      url: waspAjax.ajaxurl,
      type: "POST",
      data: {
        action: "wasp_test_url",
        url: url,
        scrape_type: scrapeType,
        nonce: waspAjax.nonce,
      },
      success: (response) => {
        if (response.success) {
          var data = response.data
          var message = "URL Test Successful!\n\n"
          message += "Status Code: " + data.status_code + "\n"
          message += "Content Length: " + formatBytes(data.content_length) + "\n"
          message += "Content Type: " + data.content_type + "\n"

          if (data.analysis) {
            message += "\nContent Analysis:\n"
            for (var key in data.analysis) {
              message += key + ": " + data.analysis[key] + "\n"
            }
          }

          alert(message)
        } else {
          alert(waspAjax.strings.error + ": " + response.data)
        }
      },
      error: () => {
        alert(waspAjax.strings.error + ": Failed to test URL")
      },
      complete: () => {
        $button.prop("disabled", false).removeClass("wasp-loading").text("Test URL")
      },
    })
  })

  // Start scraping task
  $(document).on("click", ".wasp-start-task", function () {
    var $button = $(this)
    var taskId = $button.data("task-id")

    $button.prop("disabled", true).addClass("wasp-loading").text(waspAjax.strings.starting_task)

    $.ajax({
      url: waspAjax.ajaxurl,
      type: "POST",
      data: {
        action: "wasp_start_scraping",
        task_id: taskId,
        nonce: waspAjax.nonce,
      },
      success: (response) => {
        if (response.success) {
          showProgressModal()
          monitorProgress(taskId)
        } else {
          alert(waspAjax.strings.error + ": " + response.data)
        }
      },
      error: () => {
        alert(waspAjax.strings.error + ": Failed to start scraping")
      },
      complete: () => {
        $button.prop("disabled", false).removeClass("wasp-loading").text("Start")
      },
    })
  })

  // Monitor scraping progress
  function monitorProgress(taskId) {
    progressInterval = setInterval(() => {
      $.ajax({
        url: waspAjax.ajaxurl,
        type: "POST",
        data: {
          action: "wasp_get_progress",
          task_id: taskId,
          nonce: waspAjax.nonce,
        },
        success: (response) => {
          if (response.success) {
            var progress = response.data
            updateProgress(progress.progress, progress.message, progress.details)

            if (progress.progress >= 100) {
              clearInterval(progressInterval)
              setTimeout(() => {
                hideProgressModal()
                location.reload()
              }, 2000)
            }
          } else {
            clearInterval(progressInterval)
            hideProgressModal()
          }
        },
        error: () => {
          clearInterval(progressInterval)
          hideProgressModal()
        },
      })
    }, 2000)
  }

  // Show progress modal
  function showProgressModal() {
    $("#wasp-progress-modal").fadeIn()
    updateProgress(0, "Initializing...", "")
  }

  // Hide progress modal
  function hideProgressModal() {
    $("#wasp-progress-modal").fadeOut()
  }

  // Update progress
  function updateProgress(progress, message, details) {
    $(".wasp-progress-fill").css("width", progress + "%")
    $(".wasp-progress-text").text(message)
    $(".wasp-progress-details").text(details || "")
  }

  // Create post from result
  $(document).on("click", ".wasp-create-post", function () {
    var $button = $(this)
    var resultId = $button.data("result-id")

    $button.prop("disabled", true).addClass("wasp-loading").text(waspAjax.strings.creating_post)

    $.ajax({
      url: waspAjax.ajaxurl,
      type: "POST",
      data: {
        action: "wasp_create_post",
        result_id: resultId,
        nonce: waspAjax.nonce,
      },
      success: (response) => {
        if (response.success) {
          alert(waspAjax.strings.success + ": Post created successfully!")
          $button.replaceWith(
            '<a href="' + response.data.edit_url + '" class="button button-small" target="_blank">Edit Post</a>',
          )
        } else {
          alert(waspAjax.strings.error + ": " + response.data)
        }
      },
      error: () => {
        alert(waspAjax.strings.error + ": Failed to create post")
      },
      complete: () => {
        $button.prop("disabled", false).removeClass("wasp-loading").text("Create Post")
      },
    })
  })

  // Delete task
  $(document).on("click", ".wasp-delete-task", function () {
    if (!confirm(waspAjax.strings.confirm_delete)) {
      return
    }

    var $button = $(this)
    var taskId = $button.data("task-id")

    $button.prop("disabled", true).addClass("wasp-loading")

    $.ajax({
      url: waspAjax.ajaxurl,
      type: "POST",
      data: {
        action: "wasp_delete_task",
        task_id: taskId,
        nonce: waspAjax.nonce,
      },
      success: (response) => {
        if (response.success) {
          $button.closest("tr").fadeOut(function () {
            $(this).remove()
          })
        } else {
          alert(waspAjax.strings.error + ": " + response.data)
        }
      },
      error: () => {
        alert(waspAjax.strings.error + ": Failed to delete task")
      },
      complete: () => {
        $button.prop("disabled", false).removeClass("wasp-loading")
      },
    })
  })

  // View content details
  $(document).on("click", ".wasp-view-content", function () {
    var resultId = $(this).data("result-id")

    $.ajax({
      url: waspAjax.ajaxurl,
      type: "POST",
      data: {
        action: "wasp_get_result_details",
        result_id: resultId,
        nonce: waspAjax.nonce,
      },
      success: (response) => {
        if (response.success) {
          var data = response.data
          var html = "<h4>Title</h4>"
          html += '<div class="wasp-content-preview">' + (data.title || "No title") + "</div>"
          html += "<h4>Content</h4>"
          html += '<div class="wasp-content-preview">' + (data.content || "No content") + "</div>"
          html += "<h4>URL</h4>"
          html += '<div class="wasp-content-preview">'
          if (data.url) {
            html += '<a href="' + data.url + '" target="_blank">' + data.url + "</a>"
          } else {
            html += "No URL"
          }
          html += "</div>"
          html += "<h4>Meta Information</h4>"
          html += '<div class="wasp-meta-info">'
          html += "<strong>Scraped At:</strong> " + data.scraped_at + "<br>"
          html += "<strong>Status:</strong> " + data.status + "<br>"
          if (data.image_url) {
            html += '<strong>Image:</strong> <a href="' + data.image_url + '" target="_blank">View Image</a><br>'
          }
          if (data.author) {
            html += "<strong>Author:</strong> " + data.author + "<br>"
          }
          html += "</div>"

          $("#wasp-content-details").html(html)
          $("#wasp-content-modal").fadeIn()
        } else {
          alert(waspAjax.strings.error + ": " + response.data)
        }
      },
      error: () => {
        alert(waspAjax.strings.error + ": Failed to load content details")
      },
    })
  })

  // Close modal
  $(document).on("click", ".wasp-modal-close, .wasp-modal", function (e) {
    if (e.target === this) {
      $(".wasp-modal").fadeOut()
    }
  })

  // ESC key to close modal
  $(document).keyup((e) => {
    if (e.keyCode === 27) {
      $(".wasp-modal").fadeOut()
    }
  })

  // Select all checkboxes
  $("#wasp-select-all").on("change", function () {
    $(".wasp-result-checkbox").prop("checked", $(this).prop("checked"))
  })

  // Bulk actions
  $("#wasp-apply-bulk").on("click", () => {
    var action = $("#wasp-bulk-action").val()
    var selectedIds = $(".wasp-result-checkbox:checked")
      .map(function () {
        return $(this).val()
      })
      .get()

    if (!action) {
      alert(waspAjax.strings.error + ": Please select an action")
      return
    }

    if (selectedIds.length === 0) {
      alert(waspAjax.strings.error + ": Please select at least one item")
      return
    }

    if (!confirm('Are you sure you want to perform "' + action + '" on ' + selectedIds.length + " items?")) {
      return
    }

    $.ajax({
      url: waspAjax.ajaxurl,
      type: "POST",
      data: {
        action: "wasp_bulk_action",
        action_type: action,
        result_ids: selectedIds,
        nonce: waspAjax.nonce,
      },
      success: (response) => {
        if (response.success) {
          alert(waspAjax.strings.success + ": " + response.data.message)
          location.reload()
        } else {
          alert(waspAjax.strings.error + ": " + response.data)
        }
      },
      error: () => {
        alert(waspAjax.strings.error + ": Failed to perform bulk action")
      },
    })
  })

  // Clear logs
  $("#wasp-clear-logs").on("click", () => {
    if (!confirm("Are you sure you want to clear all logs?")) {
      return
    }

    $.ajax({
      url: waspAjax.ajaxurl,
      type: "POST",
      data: {
        action: "wasp_clear_logs",
        nonce: waspAjax.nonce,
      },
      success: (response) => {
        if (response.success) {
          alert(waspAjax.strings.success + ": Logs cleared successfully")
          location.reload()
        } else {
          alert(waspAjax.strings.error + ": " + response.data)
        }
      },
      error: () => {
        alert(waspAjax.strings.error + ": Failed to clear logs")
      },
    })
  })

  // Apply filters
  $("#wasp-apply-filters, #wasp-apply-log-filter").on("click", function () {
    var url = new URL(window.location)

    if ($(this).attr("id") === "wasp-apply-filters") {
      var taskId = $("#wasp-filter-task").val()
      var status = $("#wasp-filter-status").val()

      if (taskId) {
        url.searchParams.set("task_id", taskId)
      } else {
        url.searchParams.delete("task_id")
      }

      if (status) {
        url.searchParams.set("status", status)
      } else {
        url.searchParams.delete("status")
      }
    } else {
      var level = $("#wasp-log-level-filter").val()

      if (level) {
        url.searchParams.set("level", level)
      } else {
        url.searchParams.delete("level")
      }
    }

    window.location = url
  })

  // Form validation
  $("#wasp-task-form").on("submit", (e) => {
    var taskName = $("#task_name").val().trim()
    var taskUrl = $("#task_url").val().trim()

    if (!taskName) {
      alert(waspAjax.strings.error + ": Please enter a task name")
      $("#task_name").focus()
      e.preventDefault()
      return false
    }

    if (!taskUrl) {
      alert(waspAjax.strings.error + ": Please enter a URL")
      $("#task_url").focus()
      e.preventDefault()
      return false
    }

    try {
      new URL(taskUrl)
    } catch (_) {
      alert(waspAjax.strings.error + ": Please enter a valid URL")
      $("#task_url").focus()
      e.preventDefault()
      return false
    }
  })

  // Utility functions
  function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return "0 Bytes"

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"]

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i]
  }
})
