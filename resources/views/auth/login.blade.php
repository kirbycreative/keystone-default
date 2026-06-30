<?php
page()->id = 'login';
page()->addClass('dark');
//page()->stylesheet('laravel-toolkit/resources/css/admin.css');
?>
<x-layouts.kc-default title="Login">
    <main class="flex:row justify:center align:center padding:2" style="min-height:100vh">
        <section class="w:100 padding:2 maxw:med card bg:black">
            <p class="eyebrow hidden">Keystone</p>
            <div class="block w:100">
                <svg id="logo" class="margin:x:auto" width="250" viewBox="0 0 400 400">
                    <!-- Generator: Adobe Illustrator 29.0.1, SVG Export Plug-In . SVG Version: 2.1.0 Build 192)  -->
                    <g id="Layer_1">
                        <g>
                            <path
                                d="M24,314.5h6.4v18.8l18.3-18.8h8.7l-21.6,21.4,23.1,23.8h-9l-19.4-20.8v20.8h-6.4v-45.2Z"
                                style="fill: #fff;" />
                            <path d="M73.3,314.9h26.8v5.6h-20.4v12.5h17.9v5.9h-17.9v14.4h21.1v6h-27.5v-44.4Z"
                                style="fill: #fff;" />
                            <path d="M129.7,339.3l-13.5-24.8h7.7l9,17.4,8.9-17.4h7l-12.8,24.1v21.1h-6.4v-20.4Z"
                                style="fill: #fff;" />
                            <path
                                d="M186.4,325.6c-.1-1.8-.8-3.2-1.9-4.3s-2.8-1.6-4.8-1.6-3.6.6-4.9,1.7c-1.3,1.1-1.9,2.5-1.9,4.2s.6,2.7,1.7,3.8c1.1,1.1,3.2,2.4,6.2,3.9l2.6,1.2c3.9,1.9,6.6,3.9,8.2,6s2.4,4.5,2.4,7.5-1.3,6.8-4,9c-2.7,2.2-6.3,3.3-10.9,3.3s-7.5-1.1-9.7-3.3-3.5-5.4-3.6-9.7h7v.2c0,2.2.6,3.9,1.7,5.2,1.1,1.2,2.7,1.8,4.8,1.8s4.2-.6,5.7-1.9c1.5-1.3,2.2-2.8,2.2-4.8s-.5-3-1.5-4.1c-1-1.1-3.5-2.6-7.5-4.6l-3.2-1.6c-2.9-1.4-5.1-3.1-6.6-5-1.5-2-2.2-4.2-2.2-6.8s1.3-6.3,3.9-8.5c2.6-2.2,6.1-3.3,10.3-3.3s6.9,1,9.2,3.1c2.3,2.1,3.6,4.9,3.9,8.5h-6.9Z"
                                style="fill: #fff;" />
                            <path d="M223,320.8h-11.9v-5.9h30.3v5.9h-12v38.9h-6.4v-38.9Z" style="fill: #fff;" />
                            <path
                                d="M288.9,337.1c0,6.9-1.5,12.5-4.6,16.8-3,4.3-6.9,6.5-11.6,6.5s-8.5-2.2-11.5-6.5c-3.1-4.3-4.6-9.9-4.6-16.8s1.5-12.5,4.6-16.8c3.1-4.3,6.9-6.5,11.5-6.5s8.5,2.2,11.6,6.5c3,4.3,4.6,9.9,4.6,16.8ZM272.8,354.4c2.7,0,5-1.6,6.6-4.7s2.5-7.4,2.5-12.7-.8-9.6-2.5-12.7-3.9-4.7-6.7-4.7-5,1.5-6.6,4.6c-1.7,3.1-2.5,7.3-2.5,12.7s.8,9.6,2.5,12.7c1.7,3.1,3.9,4.7,6.6,4.7Z"
                                style="fill: #fff;" />
                            <path d="M304.5,314.5h7.8l16,33.7v-33.7h6.1v45.2h-7.5l-16.3-34v34h-6.1v-45.2Z"
                                style="fill: #fff;" />
                            <path d="M353.2,314.9h26.8v5.6h-20.4v12.5h17.9v5.9h-17.9v14.4h21.1v6h-27.5v-44.4Z"
                                style="fill: #fff;" />
                        </g>
                    </g>
                    <g id="Layer_2">
                        <g>
                            <rect x="105.8" y="153.4" width="64.5" height="1.7"
                                transform="translate(-65.9 118.7) rotate(-38.3)" style="fill: #309cd2;" />
                            <rect x="135.3" y="160.9" width="1.7" height="68.7"
                                transform="translate(-101.6 183.5) rotate(-52.3)" style="fill: #c9e3f6;" />
                            <polygon
                                points="164.5 264.5 118.7 234 163.1 215.7 163.6 216.9 121.4 234.3 165.2 263.4 164.5 264.5"
                                style="fill: #65cbe4;" />
                            <path
                                d="M274.9,275.4l-42.4-10.5v-91.7c-.2-.9-1.7-6.3-6.1-11.8-6.3-8-15.2-12.3-26.7-12.9-11.4-.6-20.5,3.5-27.2,12.3-4.6,6-6.5,12.3-6.8,13.4v91.2l-40.4,10-28.9-163.8,50.9-37.4h.1c0,0,50.2-24.3,50.2-24.3l57.1,23.8,47.4,37.4v.9c-.1,0-27.4,163.4-27.4,163.4ZM99.8,112.9l28,158.9,35.1-8.7v-89.4c.1-.3,1.9-7.5,7.3-14.6,5-6.6,14.3-14.3,29.8-13.5,15.4.8,24.2,8.1,28.9,14.1,5.1,6.5,6.5,12.8,6.6,13v.2s0,89.7,0,89.7l37,9.1,26.6-159.4-45.7-36.1-55.5-23.1-48.9,23.6-49.2,36.2Z"
                                style="fill: #65cbe4;" />
                            <rect x="130" y="88.8" width="1.7" height="68.7"
                                transform="translate(-28.1 206.9) rotate(-71)" style="fill: #dae1e5;" />
                            <polygon
                                points="171.8 158.7 162.5 134.5 146.7 75.3 148.4 74.9 164.2 134 173.4 158.1 171.8 158.7"
                                style="fill: #5792b7;" />
                            <polygon points="198.4 96 148 75.8 148.3 75 198.4 95.1 252.8 74.7 253.1 75.5 198.4 96"
                                style="fill: #65cbe4;" />
                            <rect x="247.4" y="119.3" width="54.6" height="2.1"
                                transform="translate(-23.7 90.3) rotate(-17.9)" style="fill: #82d1df;" />
                            <polygon
                                points="246.1 132.2 197.9 96.2 198.9 94.9 244.8 129.2 252.2 78.7 253.8 79 246.1 132.2"
                                style="fill: #a7deea;" />
                            <rect x="268.3" y="125.8" width="1.3" height="56.9"
                                transform="translate(-29.7 237.4) rotate(-45.4)" style="fill: #d7eff3;" />
                            <rect x="218.1" y="142" width="32.4" height=".8"
                                transform="translate(-30.1 215.2) rotate(-46.7)" style="fill: #a7deea;" />
                            <polygon
                                points="235.5 264.7 234.6 263.3 278.7 234.3 233.4 216.5 286.5 175.8 287.5 177.1 236.8 216 282.4 234 235.5 264.7"
                                style="fill: #a7deea;" />
                            <circle cx="98.4" cy="112" r="6.6" style="fill: #f8fcfe;" />
                            <circle cx="164.8" cy="264" r="5.7" style="fill: #65cbe4;" />
                            <circle cx="200" cy="50.9" r="7.6" style="fill: #65cbe4;" />
                            <circle cx="300.7" cy="112" r="7.6" style="fill: #ceecf2;" />
                            <circle cx="253" cy="75.1" r="7.6" style="fill: #65cbe4;" />
                            <circle cx="147.6" cy="75.1" r="7.6" style="fill: #4274b9;" />
                            <circle cx="274.1" cy="274.8" r="7.6" style="fill: #4274b9;" />
                            <circle cx="126.5" cy="273.5" r="7.6" style="fill: #4274b9;" />
                            <circle cx="120.1" cy="234.2" r="6.4" style="fill: #44c7f4;" />
                            <circle cx="245.4" cy="130.7" r="6.4" style="fill: #68acde;" />
                            <circle cx="109" cy="174.2" r="6.4" style="fill: #65cbe4;" />
                            <circle cx="289.2" cy="174.2" r="7.6" style="fill: #c9e3f6;" />
                            <circle cx="280.5" cy="234.2" r="6.5" style="fill: #69a9dc;" />
                            <circle cx="235.1" cy="264" r="6.5" style="fill: #c9e3f6;" />
                            <circle cx="234" cy="173" r="5.5" style="fill: #c9e3f6;" />
                            <circle cx="163.4" cy="134.3" r="5.5" style="fill: #c9e3f6;" />
                            <circle cx="198.4" cy="95.6" r="6.4" style="fill: #c9e3f6;" />
                            <circle cx="163.4" cy="216.3" r="5.5" style="fill: #c9e3f6;" />
                            <circle cx="164.3" cy="174" r="5.5" style="fill: #4274b9;" />
                        </g>
                    </g>
                </svg>
            </div>
            <h1 class="font-size:1o8 text:center">Log in to your admin panel</h1>
            <p class="margin:top:0o5 text:center muted">
                Enter your credentials for <strong>{{ env('APP_DOMAIN') }}</strong> to log into keystone.
            </p>

            {!! $loginForm->build() !!}
        </section>
    </main>
</x-layouts.kc-default>
