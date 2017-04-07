function addSubscription(authGroupID)
{
    $.ajax({
        url: "/admin/subscription/new/"+authGroupID,
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 500, 250);
        }
    });
}

function editSubscription(subscriptionID)
{
    $.ajax({
        url: "/admin/subscription/edit/"+subscriptionID,
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 500, 250);
        }
    });
}