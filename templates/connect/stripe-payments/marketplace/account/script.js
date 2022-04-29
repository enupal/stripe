var elmButton = document.querySelector("#submit");

if (elmButton) {
  elmButton.addEventListener(
    "click",
    e => {
      elmButton.setAttribute("disabled", "disabled");
      var data = {
        'action' : 'enupal-stripe/utilities/get-oauth-link'
      };
      $.ajax({
        type:"POST",
        url:"enupal-stripe/get-oauth-link",
        data: data,
        dataType : 'json',
        success: function(response) {
            if (response.url){
              window.location = response.url;
            }else{
              console.log("data", response);
            }
        }.bind(this),
        error: function(xhr, status, err) {
            console.error(xhr, status, err.toString());
        }.bind(this)
    });
    },
    false
  );
}
