<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <title>bid</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="assets/css/main.css" />
        <link rel="stylesheet" href="assets/css/ie9.css" />
        <style>
            input, textarea,  button,label {
                width : 350px;
                margin: 0;

                -webkit-box-sizing: border-box; /* For legacy WebKit based browsers */
                -moz-box-sizing: border-box; /* For legacy (Firefox <29) Gecko based browsers */
                box-sizing: border-box;
            }
            div { width: 990px; margin: 5px; }
            label{ width: 780px;margin: 1px; }
            input { width: 250px;margin: 1px;left:1000px; }

            p { width: 450px; display: inline-block;margin: 1px; font-size: .8em}
            .inputError {                
                border: 2px solid red;
                background-color: yellow;
            }
            .errorMessage{
                color:red;
            }
        </style>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>       
        <script>




            function loadCategories() {
                $("#categoryList").html("");
                var ID = $("#mainCategoryList").val();
                ///api.php/todoitems/" + currentID
                $.ajax({
                    url: "/client.php/category/" + ID,
                    // data: {},

                    statusCode: {
                        401: function (xhr) {
                            // $("#loginPane").show();
                            // $("#listPane").hide();
                        }
                    },
                    type: "GET",
                    dataType: "json"
                }).done(function (data) {

                    var opts = "";
                    for (var i = 0; i < data.length; i++) {
                        opts += '<option value="' + data[i].ID + '">' + data[i].name + '</option>';
                    }
                    $("#categoryList").html(opts);
                });
            }
            function showMainCategory() {

                $.ajax({
                    url: "/client.php/maincategory",
                    // data: {},

                    statusCode: {
                        401: function (xhr) {
                            // $("#loginPane").show();
                            // $("#listPane").hide();
                        }
                    },
                    type: "GET",
                    dataType: "json"
                }).done(function (data) {
                    // var sel = $('#categoryList');
                    var opts = "";
                    for (var i = 0; i < data.length; i++) {
                        opts += '<option value="' + data[i].ID + '">' + data[i].name + '</option>';
                    }
                    $("#mainCategoryList").html(opts);
                    $("#mainCategoryList").val(1);
                    loadCategories();
                });

            }
            function additem() {
                var userID1 = 1;
                var categoryID1 = $("#categoryList").val();
                var name1 = $("input[name=name]").val();
                var description1 = $("input[name=description]").val();
                var itemPic1 = $("input[name=itemPic]").val();
                var bidType1 = $("input[name=bidType]").val();
                var minimumBid1 = $("input[name=minimumBid]").val();
                var bidStartTime11 = $("input[name=bidStartTime]").val();
                var bidStartDate11 = $("input[name=bidStartDate]").val();
                var d1 = bidStartDate11.split(' ');
                var bidStartTime1 = d1[0] + " " + bidStartTime11;
                var bidEndTime11 = $("input[name=bidEndTime]").val();
                var bidEndDate11 = $("input[name=bidEndDate]").val();
                var d2 = bidEndDate11.split(' ');
                var bidEndTime1 = d2[0] + " " + bidEndTime11;
                var shippingFee1 = $("input[name=shippingFee]").val();
                // INSERT

                $.ajax({
                    url: "/itemsforsell",
                    statusCode: {
                        400: function (xhr) {
                            var msg = xhr.responseText;
                            alert("400 received: " + msg);
                        }
                    },
                    data: JSON.stringify({
                        categoryID: categoryID1,
                        userID: userID1,
                        name: name1,
                        //  itemPic:itemPic,
                        description: description1,
                        bidType: bidType1,
                        minimumBid: minimumBid1,
                        bidStartTime: bidStartTime1,
                        bidEndTime: bidEndTime1,
                        shippingFee: shippingFee1

                    }),
                    type: "POST",
                    dataType: "json"
                }).done(function () {
                    alert("Addedd successfully");

                });

            }


            function fullDate(d, t) {
                var dd = d.split("-");
                var date = dd[0] + "" + dd[1] + "" + dd[2];
                var tt = t.split(":");
                var time = tt[0] + "" + tt[1];
                return date + "" + time;
            }

            function standardPeriod() {
                var CurrentDate = new Date();
                CurrentDate.setMonth(CurrentDate.getMonth());

                var day = CurrentDate.getDate();
                var month = CurrentDate.getMonth() + 1;
                var year = CurrentDate.getFullYear();

                if (month < 10)
                    month = "0" + month;
                if (day < 10)
                    day = "0" + day;

                var today = year + "-" + month + "-" + day;

                return today;
            }
            function standardPeriodTime() {
                var CurrentDate = new Date();
                CurrentDate.setHours(CurrentDate.getHours());

                var h = CurrentDate.getHours();
                var m = CurrentDate.getMinutes();
                //var year = CurrentDate.getFullYear();
                if (h < 10)
                    h = "0" + h;
                if (m < 10)
                    m = "0" + m;


                var today = h + ":" + m;

                return today;
            }
            $(document).ready(function () {
                //$('#bidEndDate').val(new Date().toDateInputValue());

                //  var today = new Date();
                $('#bidEndDate').val(standardPeriod());
                $('#bidStartDate').val(standardPeriod());
                $('#bidStartTime').val(standardPeriodTime());
                $('#bidEndTime').val(standardPeriodTime());
                // $('#bidStartDate').val(new Date().toDateInputValue());
                $(".errorMessage").hide();
                showMainCategory();
                $('#registrationForm').submit(function (event) {
                    var categoryID1 = $("#categoryList").val();
                    var name1 = $("input[name=name]").val();
                    var description1 = $("#description").val();
                    var itemPic1 = $("input[name=itemPic]").val();
                    var bidType1 = $("input[name=bidType]").val();
                    var minimumBid1 = $("input[name=minimumBid]").val();
                    var bidEndDate1 = $('#bidEndDate').val();
                    var itemPic1 = $("input[name=itemPic]").val();
                    var bidStartDate1 = $('#bidStartDate').val();
                    var bidStartTime1 = $('#bidStartTime').val();
                    var bidEndTime1 = $('#bidEndTime').val();
                    var allGood = true;
                    if (name1.length < 3) {
                        allGood = false;
                        $("#namee").show();
                    } else {
                        $("#namee").hide();
                    }
                    if (description1.length < 5) {
                        allGood = false;
                        $("#descriptione").show();
                    } else {
                        $("#descriptione").hide();
                    }

                    if (minimumBid1 < 1) {
                        allGood = false;
                        $("#minimumBide").show();
                    } else {
                        $("#minimumBide").hide();
                    }
                    // if (bidType1.length < 2) {
                    //    allGood = false;
                    //}
                    if (itemPic1 == "") {
                        allGood = false;
                        $("#itemPice").show();
                    } else {
                        $("#itemPice").hide();
                    }

                    if (fullDate(bidStartDate1, bidStartTime1) >= fullDate(bidEndDate1, bidEndTime1)) {
                        allGood = false;
                        $("#timee").show();

                    } else {
                        $("#timee").hide();
                    }

                    //
                    if (!allGood) {
                        alert("Invalid values");
                        event.preventDefault();
                    }
                });
                $("#addSell").click(function () {
                    // FIXME: verify inputs seem okay,
                    // * title is at least 1-100 chars long
                    // * dueDate must be chosen


                });




            });

        </script>
    </head>
    <body>

        <form method="post" enctype="multipart/form-data" action="/itemsforsell" id="registrationForm">
            <div class="container">
                <select name="maincategoryList" id="mainCategoryList" onchange="loadCategories()">               
                </select><br>
                <select name="categoryList" id="categoryList">               
                </select><br>
                <div>
                    <label for="name">Sell title: </label>
                    <input type="text" name="name" id="name" class="mycss">
                    <p class="errorMessage" id="namee">title must be 3-100 characters </p>
                </div>
                <div>
                    <label for="description">Description</label>
                    <textarea rows="4" cols="150" name="description" id="description" class="mycss"></textarea>
                  <!---  <input type="text" name="description" id="description" class="mycss">--->
                    <p class="errorMessage" id="descriptione">description must be  more than 5 characters </p>
                    <!--- <form action="upload.php" method="post" enctype="multipart/form-data">--->
                </div>
                <div>
                    <label for="itemPic">Choose item picture:</label>
                    <input type="file" name="itemPic" id="itemPic">
                    <p class="errorMessage" id="itemPice">image of your goods must be provided </p>
                </div>
                <div>
                    <label for="itemPic"> Bid Type:</label>
                    <input type="text" name="bidType" id="bidType" class="mycss">
                </div>
                <div>
                    <label for="minimumBid"> Minimum Bid Amount(CAD):</label>
                    <input type="number" step="0.10" name="minimumBid"  id="minimumBid" class="mycss">
                    <p class="errorMessage" id="minimumBide">your amount must be greater than 1 CAD </p>
                </div>
                <div>
                    <label for="bidStartDate"> Bid Start Date:</label>
                    <input class="mycss" type="date"  name="bidStartDate" id="bidStartDate"  >
                    <label for="bidStartTime"> Bid Start Time:</label>
                    <input type="time"  name="bidStartTime" id="bidStartTime">
                </div>
                <div>

                    <label for="bidEndDate">Bid End Date:</label>
                    <input class="mycss" type="date"  name="bidEndDate" id="bidEndDate" >
                    <label for="bidEndTime"> Bid Start Time:</label>
                    <input type="time"  class="mycss" name="bidEndTime" id="bidEndTime">
                    <p class="errorMessage" id="timee">all times and date must be provided and end date must be later than start date  </p>
                </div>
                <div>
                    <label for="shippingFee">  Shipping Fee(CAD)</label>
                    Shipping Fee(CAD):<input type="number" step="0.01" name="shippingFee" class="mycss" value="0"><br>
                </div>
                <input type="submit" value="Add your Item for sell"  >
                </form>

            </div>
    </body>
</html>


