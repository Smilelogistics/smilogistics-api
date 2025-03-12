<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
            /* * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
            } */

            :root {
                --dark: #34495e;
                --light: #fff;
                --success: #0abf30;
                --error: #f24d4c;
                --warning: #e9bd0c;
                --info: #3498db;
            }

            /* body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: var(--dark);
            } */

            .Toastnotifications {
                position: fixed;
                top: 30px;
                right: 20px;
            }

            .Toastnotifications :where(.toast, .column) {
                display: flex;
                align-items: center;
            }

            .Toastnotifications .Toasttoast {
                width: 400px;
                position: relative;
                overflow: hidden;
                list-style: none;
                border-radius: 4px;
                padding: 16px 17px;
                margin-bottom: 10px;
                background: var(--light);
                justify-content: space-between;
                animation: show_toast 0.3s ease forwards;
            }

            @keyframes show_toast {
                0% {
                    transform: translateX(100%);
                }
                40% {
                    transform: translateX(-5%);
                }
                80% {
                    transform: translateX(0%);
                }
                100% {
                    transform: translateX(-10%);
                }
            }

            .Toastnotifications .Toasttoast.hide {
                animation: hide_toast 0.3s ease forwards;
            }

            @keyframes hide_toast {
                0% {
                    transform: translateX(-10%);
                }
                40% {
                    transform: translateX(0%);
                }
                80% {
                    transform: translateX(-5%);
                }
                100% {
                    transform: translateX(calc(100% + 20px));
                }
            }

            .Toasttoast::before {
                position: absolute;
                content: '';
                height: 3px;
                width: 100%;
                bottom: 0;
                left: 0;
                animation: progress 5s linear forwards;
            }

            @keyframes progress {
                100% {
                    width: 0%;
                }
            }

            .Toasttoast.success::before, .btn#success {
                background: var(--success);
            }

            .Toasttoast.error::before, .btn#error {
                background: var(--error);
            }

            .Toasttoast.warning::before, .btn#warning {
                background: var(--warning);
            }

            .Toasttoast.info::before, .btn#info {
                background: var(--info);
            }

            .Toasttoast .column i {
                font-size: 1.75rem;
            }

            .Toasttoast.success .column i {
                color: var(--success);
            }

            .Toasttoast.error .column i {
                color: var(--error);
            }

            .Toasttoast.warning .column i {
                color: var(--warning);
            }

            .Toasttoast.info .column i {
                color: var(--info);
            }

            .Toasttoast .column span {
                font-size: 1.07rem;
                margin-left: 12px;
            }

            .Toasttoast i:last-child {
                color: #aeb0d7;
                cursor: pointer;
            }

            .Toasttoast i:last-child:hover {
                color: var(--dark);
            }

            .Toastbuttons .Toastbtn {
                border: none;
                outline: none;
                color: var(--light);
                cursor: pointer;
                margin: 0 5px;
                font-size: 1.2rem;
                padding: 10px 20px;
                border-radius: 4px;
            }

            @media screen and (max-width: 530px) {
                .Toastnotifications {
                    width: 80%;
                }
                
                .Toastnotifications .Toasttoast {
                    width: 100%;
                    font-size: 1rem;
                    margin-left: 20px;
                }
                
                .Toastbuttons .Toastbtn {
                    margin: 0 1px;
                    font-sze: 1.1rem;
                    padding: 8px 15px;
                }
            }
        </style>
    </head>
    <body>
        <div class="font-sans text-gray-900 antialiased">
            <ul class="Toastnotifications">
                <!-- li toast -->
            </ul>
            <div class="Toastbuttons" style="display: none">
                <button class="Toastbtn" id="success">Success</button>
                <button class="Toastbtn" id="error">Error</button>
                <button class="Toastbtn" id="warning">Warning</button>
                <button class="Toastbtn" id="info">Info</button>
            </div>
            {{ $slot }}
        </div>

        @livewireScripts

        <script>
            $(document).ready(function() {
            
                const notifications = $(".Toastnotifications");
                const toastDetails = {
                    timer: 5000,
                    success: {
                        icon: "fa-circle-check",
                    },
                    error: {
                        icon: "fa-circle-xmark",
                    }
                };
                window.createToast = function(id, text) {
                    const { icon } = toastDetails[id];
                    const toast = $("<li>").addClass(`Toasttoast ${id}`).html(`
                        <div class="column">
                            <i class="fa-solid ${icon}"></i>
                            <span>${text}</span>
                        </div>
                        <i class="fa-solid fa-xmark" onclick="removeToast($(this).parent())"></i>
                    `);
                    notifications.append(toast);
                    toast[0].timeoutId = setTimeout(() => removeToast(toast), toastDetails.timer);
                };

                const removeToast = (toast) => {
                    toast.addClass("hide");
                    if (toast.timeoutId) clearTimeout(toast.timeoutId);
                    setTimeout(() => toast.remove(), 500);
                };
        })
        </script>
    </body>
</html>
