jQuery(document).ready(function ($) {
  // Single role update
  $(".update-role").on("click", function () {
    const $row = $(this).closest("tr");
    const userId = $row.data("user-id");
    const newRole = $row.find(".role-select").val();

    updateUserRole(userId, newRole, $row);
  });

  // Bulk actions
  $("#bulk-apply").on("click", function () {
    const newRole = $("#bulk-action-selector").val();
    if (!newRole) return;

    $(".user-select:checked").each(function () {
      const $row = $(this).closest("tr");
      const userId = $row.data("user-id");
      updateUserRole(userId, newRole, $row);
    });
  });

  // Select all checkbox
  $("#select-all").on("change", function () {
    $(".user-select").prop("checked", $(this).prop("checked"));
  });

  function updateUserRole(userId, newRole, $row) {
    $.ajax({
      url: cknAdmin.ajax_url,
      type: "POST",
      data: {
        action: "update_user_role",
        user_id: userId,
        new_role: newRole,
        nonce: cknAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          $row.find(".user-role").text(response.data.new_role);
          $row
            .addClass("updated")
            .delay(2000)
            .queue(function () {
              $(this).removeClass("updated").dequeue();
            });
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        alert("Server error occurred");
      },
    });
  }
});
