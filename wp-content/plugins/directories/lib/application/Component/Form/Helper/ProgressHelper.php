<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form\AbstractMultiStepController;

class ProgressHelper
{
    public function formSubmitJs(Application $application)
    {
        return sprintf(
            'function (form, trigger, ajaxOptions) {
    form.slideUp("fast");
    var headers = form.parent().find(".drts-form-headers");
    if (headers.length) {
        headers.hide();
    }
    var modal = trigger.closest(".%1$smodal-content");
    if (modal.length) {
        modal.find(".drts-form-buttons").remove();
    }
    $(".drts-system-progress")
        .find(".drts-system-progress-bar > div").css("background-color", "#ccc").end()
        .slideDown("fast");
    var options = $.extend({}, ajaxOptions);
    var successCallback = options["onSuccess"] || function () {};
    var request = function (next) {
        options["data"] += "&next=" + next;
        DRTS.ajax(options);
    };
    var processResult = function(result, target, trigger) {
        var done = result.done || typeof result.next === "undefined";
        var $progress = $(".drts-system-progress");
        if (result.percent || done) {
            var percent = done ? 100 : result.percent;
            $progress.find(".drts-system-progress-bar > div").css({"background-color": "", width: percent + "%%"}).end()
                .find(".drts-system-progress-percent").text(percent + "%%");
        }
        ["error", "warning", "info", "success"].forEach(function (level) {
            if (result[level]
                && result[level].length
            ) {
                if (!Array.isArray(result[level])) {
                    result[level] = [result[level]];
                }
                $("<div class=\"%1$salert %1$salert-dismissible %1$sfade %1$sshow %1$salert-" + (level === "error" ? "danger" : level) + "\" style=\"display:none;\"><p class=\"%1$smy-1\">" + result[level].join("</p><p>") + "</p><button type=\"button\" class=\"%1$sclose\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button></div>")
                    .insertAfter($progress)
                    .slideDown("fast");
            }
        });
        
        if (done) {
            $progress.find(".drts-system-progress-message").text(result.message ? result.message : "%2$s").end()
                .find(".drts-system-progress-bar > div").removeClass("%1$sprogress-bar-animated");
            successCallback(result, target, trigger);
        } else {
            if (result.message) {
                $progress.find(".drts-system-progress-message").text(result.message);
            }
            request(result.next); 
        }
    };
    options["onSuccess"] = processResult;
    ajaxOptions["onSuccess"] = function (result, target, trigger) {
        processResult(result, target, trigger);
    }  
}',
            DRTS_BS_PREFIX,
            $application->H(__('All done. Have fun!!', 'directories'))
        );
    }
    
    public function onSuccessJs(Application $application)
    {
        $js = 'if (result.download_file) {
    var btn = target.find(".drts-system-download");
    if (btn.length) {
        var modal = btn.closest(".' . DRTS_BS_PREFIX . 'modal-content");
        if (modal.length) {
            modal.find(".' . DRTS_BS_PREFIX . 'modal-footer").append(btn);
        }
        btn.slideDown("fast").on("click", function(e){
            e.preventDefault();
            var href = $(this).attr("href");
            window.location = href + (href.indexOf("?") === -1 ? "?" : "&") + "file=" + result.download_file;
        }).click();
    }
} else if (result.url) {
    if (target && target.attr("id") === "drts-modal") {
        setTimeout(function(){
            target.sabaiModal("hide");
            window.location = result.url;
        }, 500);
    }
}';
        return 'function (result, target, trigger) {' . $js . '}';
    }
}