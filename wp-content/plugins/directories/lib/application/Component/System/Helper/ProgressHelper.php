<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\System\Progress;
use SabaiApps\Directories\Request;

class ProgressHelper
{
    public function help(Application $application, $name)
    {
        return new Progress($application, $name);
    }
        
    public function get(Application $application, $name)
    {
        if (!$ret = $application->getPlatform()->getOption('system_progress_' . $name)) {
            $ret = array(
                'message' => __('Failed getting progress data.', 'directories'),
            );
        }
        return $ret;
    }
    
    public function set(Application $application, $name, array $data)
    {
        // We need to use setOption instead of setCache so that progress data is not deleted
        // even when cache is cleared while the progress is active.
        $application->getPlatform()->setOption('system_progress_' . $name, $data, false);
    }
    
    public function formSubmitJs(Application $application, $name)
    {
        $url = $application->Url('/_drts/system/progress', [Request::PARAM_CONTENT_TYPE => 'json', 'name' => $name], '', '&');
        return 'function (form, trigger) {
    form.slideUp("fast");
    var headers = form.parent().find(".drts-form-headers");
    if (headers.length) {
        headers.hide();
    }
    var modal = trigger.closest(".' . DRTS_BS_PREFIX . 'modal-content");
    if (modal.length) {
        modal.find(".drts-form-buttons").remove();
    }
    var progress = $(".drts-system-progress")
        .find(".drts-system-progress-bar > div").css("background-color", "#ccc").end()
        .slideDown("fast");
    var initialMessage = $(".drts-system-progress").find(".drts-system-progress-message").html();
    var setProgressData = function(data) {
        if (typeof data.message === "string") {
            progress.find(".drts-system-progress-message").html(data.message);
        }
        if (data.percent !== "undefined") {
            if (data.percent == -1) {
                progress.find(".drts-system-progress-bar > div").css("width", "100%").end()
                    .find(".drts-system-progress-percent").text("");
            } else {
                progress.find(".drts-system-progress-bar > div").css({"background-color": "", width: data.percent + "%"}).end()
                    .find(".drts-system-progress-percent").text(data.percent + "%");
            }
        }
    };
    var timer = window.setInterval(function() {
        $.ajax({
            url: "' . $url . '",
            success: function(data) {
                if (typeof data.percent === "undefined"
                    || data.done
                ) {
                    progress.find(".drts-system-progress-bar > div").removeClass("' . DRTS_BS_PREFIX . 'progress-bar-animated");
                    if (!data.more) {
                        setProgressData(data); 
                        window.clearInterval(timer);
                    } else {
                        if (progress.data("more") != data.more) {
                            setProgressData(data); 
                            progress = progress.clone().data("more", data.more).css("display", "none").insertAfter(progress);
                            setProgressData({message: initialMessage, percent: 0}); 
                            progress.slideDown("fast");
                        }
                    }
                } else {
                    setProgressData(data); 
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    }, 1000);
}';
    }
    
    public function formSuccessDownloadJs(Application $application, array $options = [])
    {
        $options += array(
            'download_param' => 'download_file',
        );
        $js = 'if (result.' . $application->H($options['download_param']) . ') {
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
}';     
        return 'function (result, target, trigger) {' . $js . '}';
    }
}