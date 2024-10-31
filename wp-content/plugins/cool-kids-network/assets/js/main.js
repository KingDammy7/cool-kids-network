jQuery(document).ready(function ($) {
  $("#ckn-signup").on("submit", function (e) {
    e.preventDefault();

    const email = $("#email").val();
    const $form = $(this);
    const $message = $form.find(".response-message");
    const $submit = $form.find('button[type="submit"]');

    // Disable submit button
    $submit.prop("disabled", true);

    // Clear previous messages
    $message.removeClass("error success").empty();

    $.ajax({
      url: ckn_ajax.ajax_url,
      type: "POST",
      data: {
        action: "ckn_register_user",
        email: email,
        nonce: ckn_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          $message
            .addClass("success")
            .text("Registration successful! Redirecting...");

          // Redirect to character page after 2 seconds
          setTimeout(function () {
            window.location.href = response.data.redirect;
          }, 2000);
        } else {
          $message
            .addClass("error")
            .text(response.data || "An error occurred. Please try again.");
          $submit.prop("disabled", false);
        }
      },
      error: function () {
        $message.addClass("error").text("An error occurred. Please try again.");
        $submit.prop("disabled", false);
      },
    });
  });
});
