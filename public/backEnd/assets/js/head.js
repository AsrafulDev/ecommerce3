var softmitAppStyle = document.getElementById("app-style");
if (softmitAppStyle && softmitAppStyle.href && softmitAppStyle.href.includes("rtl.min.css")) {
    document.getElementsByTagName("html")[0].dir = "rtl";
}
