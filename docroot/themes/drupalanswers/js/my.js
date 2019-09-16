    /**
     * @file
     * Global utilities.
     *
     */
    (function ($, Drupal) {

        'use strict';

        Drupal.behaviors.drupalanswers = {
            attach: function (context, settings) {


                $(document).ready(function () {
                            $("[id^=autocomplete-deluxe-input]").attr("placeholder", "tags");

                    $("[class^=js-view-dom-id]").css("display", "inline");
                    //                    $('#sidebar_first').addClass('pr-0 pl-0');
                    //                    $('.main-content').addClass('p-0');
                    $('.views-element-container.contextual-region.col-auto').addClass('p-0');

                    var role = $(".user-role:first").text();
                    $('.bdg h5').each(function () {
                        //alert($(this).text());
                        var v = parseInt($(this).text());
                        if ($(this).text() > 0) {

                            $(this).addClass("bg-success text-light");
                        }
                    });




                    // $.stickysidebarscroll("#sidebar_first .sf", {
                    //     offset: {
                    //         top: 10,
                    //         bottom: 200,
                    //     }
                    // });
                    //                        $("#sidebar_first, #sidebar_first .sf   ").addClass("m-0 p-0");
                    //                        $(".main-content").addClass("p-0");
                    jQuery(".main-content .video").append(jQuery(".main-content .views-field-field-video"));
                    $(".indented").addClass("offset-1");
                    jQuery(".indented > article > .answer").removeClass("pr-3");
                    jQuery(".indented > article > .answer > .comment__content").addClass("pr-0");
                    jQuery(".dp .content > .col-auto").addClass("p-0");

                    //                        APpend Vote to main Question Section
                    jQuery(".middle-quetion").each(function (id, doEle) {
                        var t = jQuery(this).find(".vote-result p").text();
                        jQuery(this).find(".append-vote  p").text(t);

                    });
                    $('.vote-result p').hide();


                    jQuery(".related-video-row").each(function (index) {
                        var video = jQuery(this).find('.field-content.related-video');
                        jQuery(this).find('.related-video-wrapper').append(video);
                    });

                    // jQuery('.field-content.related-video').hide();
                    jQuery(".related-video-row video, .related-video-row video").removeAttr("controls");

                    // jQuery(".middel-question").each(function (index) {
                    //   alert(index);
                    // });


                });
            }
        };

    })(jQuery, Drupal);

    jQuery(document).ready(function() {
        jQuery(".main-content .video").append(jQuery(".main-content .views-field-field-video-link"));
        jQuery(".main-content .video").append(jQuery(".main-content .views-field-field-video-file"));

        jQuery('.middle-quetion').each(function (index) {
          var f_video = jQuery(this).find(".append-video");
          jQuery(this).find(".front-video-wrapper").append(f_video);
        });
    });
