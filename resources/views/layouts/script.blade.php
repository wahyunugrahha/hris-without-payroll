<script src="{{ asset_v('assets/js/lib/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset_v('assets/js/lib/popper.min.js') }}"></script>
<script src="{{ asset_v('assets/js/lib/bootstrap.min.js') }}"></script>

<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

<script src="{{ asset_v('assets/js/plugins/owl-carousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset_v('assets/js/plugins/jquery-circle-progress/circle-progress.min.js') }}"></script>

<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>


<script src="{{ asset_v('assets/js/base.js') }}"></script>
{{-- Commented out unused chart code --}}
{{-- <script>
    am4core.ready(function() {

        am4core.useTheme(am4themes_animated);

        var chart = am4core.create("chartdiv", am4charts.PieChart3D);
        chart.hiddenState.properties.opacity = 0;

        chart.legend = new am4charts.Legend();

        chart.data = [{
                country: "Hadir",
                litres: 501.9
            },
            {
                country: "Sakit",
                litres: 301.9
            },
            {
                country: "Izin",
                litres: 201.1
            },
            {
                country: "Terlambat",
                litres: 165.8
            },
        ];



        var series = chart.series.push(new am4charts.PieSeries3D());
        series.dataFields.value = "litres";
        series.dataFields.category = "country";
        series.alignLabels = false;
        series.labels.template.text = "{value.percent.formatNumber('#.0')}%";
        series.labels.template.radius = am4core.percent(-40);
        series.labels.template.fill = am4core.color("white");
        series.colors.list = [
            am4core.color("#1171ba"),
            am4core.color("#fca903"),
            am4core.color("#37db63"),
            am4core.color("#ba113b"),
        ];
    });
</script> --}}

@stack('myscript')
