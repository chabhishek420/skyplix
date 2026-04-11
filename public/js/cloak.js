/**
 * Client-Side Browser Fingerprinting for Cloaking
 * Collects Canvas, WebGL, AudioContext, Timezone, and other signals
 * to detect bots and headless browsers.
 */

(function() {
    'use strict';

    // BotDetector class - mimics YellowCloaker's approach
    class BotDetector {
        constructor(options) {
            this.timeout = options.timeout || 3000;
            this.callback = options.callback;
            this.tests = options.tests || ['canvas', 'webgl', 'audio', 'timezone'];
            this.tzStart = options.tzStart || 0;
            this.tzEnd = options.tzEnd || 100;
            this.results = {
                isBot: false,
                reason: '',
                score: 0,
                canvas: false,
                webgl: false,
                audio: false,
                timezone: false,
                plugins: false,
                languages: false,
                webrtc: false,
                screenMatch: false,
                screen: null,
                timezoneOffset: null,
                userAgent: navigator.userAgent,
                platform: navigator.platform,
                vendor: navigator.vendor || '',
                languages: [],
                canvasHash: '',
                webglHash: '',
                audioHash: '',
                plugins: [],
                // Additional fingerprinting
                hardwareConcurrency: navigator.hardwareConcurrency || 0,
                deviceMemory: navigator.deviceMemory || 0,
                maxTouchPoints: navigator.maxTouchPoints || 0,
                doNotTrack: navigator.doNotTrack || 'not specified',
                cookieEnabled: navigator.cookieEnabled,
                javaEnabled: navigator.javaEnabled ? navigator.javaEnabled() : false,
                webdriver: navigator.webdriver || false,
                // Timing fingerprints
                timingBaseline: 0,
                timingDelta: 0,
            };
        }

        monitor() {
            const startTime = performance.now();
            this.results.timingBaseline = startTime;

            // Run all tests
            Promise.all([
                this.testCanvas(),
                this.testWebGL(),
                this.testAudio(),
                this.testTimezone(),
                this.testPlugins(),
                this.testLanguages(),
                this.testWebRTC(),
                this.testScreen(),
                this.testWebDriver(),
            ]).then(() => {
                this.results.timingDelta = performance.now() - startTime;
                this.evaluate();
                this.callback(this.results);
            }).catch(err => {
                this.results.isBot = true;
                this.results.reason = 'fingerprint_error';
                this.results.error = err.message;
                this.callback(this.results);
            });
        }

        async testCanvas() {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = 200;
                canvas.height = 50;
                canvas.style.display = 'inline';

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    this.results.canvas = false;
                    return;
                }

                // Draw text with various fonts
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.fillStyle = '#f60';
                ctx.fillRect(0, 0, 100, 25);
                ctx.fillStyle = '#069';
                ctx.fillText('Bot Detection', 2, 15);
                ctx.strokeStyle = 'rgba(102, 204, 0, 0.7)';
                ctx.strokeText('Cloak Test', 4, 17);

                // Try to get image data
                try {
                    const imageData = ctx.getImageData(0, 0, 200, 50);
                    const data = imageData.data;
                    
                    // Check if we got valid image data (not all zeros or random)
                    let nonZero = 0;
                    for (let i = 0; i < data.length; i += 4) {
                        if (data[i] > 0 || data[i+1] > 0 || data[i+2] > 0) {
                            nonZero++;
                        }
                    }
                    
                    // Hash the canvas data
                    this.results.canvasHash = this.hashArray(data);
                    this.results.canvas = nonZero > 100;
                } catch (e) {
                    // Security error - likely headless browser
                    this.results.canvas = false;
                    this.results.reason = 'canvas_blocked';
                }
            } catch (e) {
                this.results.canvas = false;
            }
        }

        async testWebGL() {
            try {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                
                if (!gl) {
                    this.results.webgl = false;
                    return;
                }

                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                const vendor = gl.getParameter(gl.VENDOR);
                const renderer = debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : '';

                // Create a simple WebGL scene and read pixels
                gl.clearColor(0.5, 0.7, 0.9, 1.0);
                gl.clear(gl.COLOR_BUFFER_BIT);

                const pixels = new Uint8Array(4);
                gl.readPixels(50, 50, 1, 1, gl.RGBA, gl.UNSIGNED_BYTE, pixels);

                // Hash WebGL params
                const webglStr = vendor + '|' + renderer + '|' + pixels.join(',');
                this.results.webglHash = this.hashString(webglStr);
                
                // Check for known headless GPU patterns
                const isHeadless = renderer.match(/swiftshader|llvmpipe|software|headless/i);
                this.results.webgl = !isHeadless && (vendor.length > 0 || renderer.length > 0);
                
                this.results.webglVendor = vendor;
                this.results.webglRenderer = renderer;
            } catch (e) {
                this.results.webgl = false;
            }
        }

        async testAudio() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                // Create oscillator and analyser
                const oscillator = audioContext.createOscillator();
                const analyser = audioContext.createAnalyser();
                const gain = audioContext.createGain();
                const processor = audioContext.createScriptProcessor(4096, 1, 1);

                gain.gain.value = 0;
                oscillator.type = 'triangle';
                oscillator.connect(analyser);
                analyser.connect(processor);
                processor.connect(gain);
                gain.connect(audioContext.destination);

                oscillator.start(0);

                // Collect audio fingerprint
                const fingerprint = new Float32Array(analyser.frequencyBinCount);
                analyser.getFloatFrequencyData(fingerprint);

                // Calculate hash
                let sum = 0;
                for (let i = 0; i < fingerprint.length; i++) {
                    sum += Math.abs(fingerprint[i]);
                }

                this.results.audioHash = this.hashArray(fingerprint);
                this.results.audio = sum > 0; // Real browsers have non-zero audio data

                // Cleanup
                oscillator.stop();
                audioContext.close();

                // If AudioContext fails or returns zeros, likely headless
                if (sum === 0) {
                    this.results.audio = false;
                }
            } catch (e) {
                this.results.audio = false;
            }
        }

        async testTimezone() {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const offset = new Date().getTimezoneOffset();
            
            this.results.timezoneOffset = offset;
            this.results.timezoneName = tz;

            // Check if timezone is in the expected range (tzStart to tzEnd)
            // Offset is in minutes, negative = positive timezone (e.g., US East = -300)
            this.results.timezone = offset >= this.tzStart && offset <= this.tzEnd;
            
            // Additional check: see if timezone is consistent
            // Bots often have inconsistent timezone
            const now = new Date();
            const utc = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours());
            const tzOffset = now.getTimezoneOffset() * 60000;
            const local = new Date(utc - tzOffset);
            
            // If timezone offset gives us a different day, suspicious
            if (now.getDate() !== local.getDate()) {
                this.results.timezone = false;
            }
        }

        async testPlugins() {
            try {
                const plugins = [];
                for (let i = 0; i < navigator.plugins.length; i++) {
                    plugins.push({
                        name: navigator.plugins[i].name,
                        filename: navigator.plugins[i].filename
                    });
                }
                this.results.plugins = plugins;
                // Real browsers have plugins (even if just PDF viewer)
                // Headless browsers typically have 0 plugins
                this.results.hasPlugins = plugins.length > 0;
            } catch (e) {
                this.results.hasPlugins = false;
            }
        }

        async testLanguages() {
            try {
                const langs = navigator.languages || [navigator.language];
                this.results.languages = langs;
                // Check for realistic language settings
                // Bots might have empty, 'en', or unusual combinations
                this.results.languagesValid = langs.length > 0 && langs[0].length >= 2;
            } catch (e) {
                this.results.languagesValid = false;
            }
        }

        async testWebRTC() {
            try {
                if (!window.RTCPeerConnection && !window.webkitRTCPeerConnection) {
                    this.results.webrtc = false;
                    return;
                }

                // Check if WebRTC is actually available
                const pc = new (window.RTCPeerConnection || window.webkitRTCPeerConnection)({
                    iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
                });

                pc.createDataChannel('test');
                this.results.webrtc = true;
                
                try {
                    pc.createOffer();
                } catch (e) {
                    // Expected - we're just checking if WebRTC API exists
                }
            } catch (e) {
                this.results.webrtc = false;
            }
        }

        async testScreen() {
            try {
                this.results.screen = {
                    width: screen.width,
                    height: screen.height,
                    availWidth: screen.availWidth,
                    availHeight: screen.availHeight,
                    colorDepth: screen.colorDepth,
                    pixelDepth: screen.pixelDepth
                };

                // Check for unusual screen configurations
                // Bots often have 0x0 or unrealistic dimensions
                const isRealistic = screen.width > 0 && screen.height > 0 && 
                                   screen.width <= 7680 && screen.height <= 4320; // 8K max
                
                // Headless browsers often report 800x600 or 0x0
                const isNotHeadless = screen.width !== 800 || screen.height !== 600;
                
                this.results.screenMatch = isRealistic && isNotHeadless;
            } catch (e) {
                this.results.screenMatch = false;
            }
        }

        async testWebDriver() {
            // Detect Selenium/ChromeDriver/Puppeteer
            try {
                // Check for webdriver flag
                if (navigator.webdriver) {
                    this.results.webdriver = true;
                    return;
                }

                // Check for Selenium/Automation signatures
                const testKeys = [
                    '_selenium',
                    '__selenium_evaluate',
                    '__webdriver_evaluate',
                    '__driver_evaluate',
                    '__webdriver_script_function',
                    '__webdriver_script_func',
                    '__webdriver_script_nonce',
                    'webdriver',
                    '__webdriver',
                    'cdc_',
                    'ChromeForTesting',
                    'consoleLength',
                    '__chromeJSON',
                    'callSelenium',
                    '_WEBDRIVER_SCRIPT',
                    'selenium',
                    'automate_',
                    'A9T9Kadi_xf s254',
                    'javascriptEnabled',
                    '__webdriver_script_nonce',
                ];

                // Check Chrome runtime signs
                const test1 = window.callSelenium;
                const test2 = window.webdriver;
                const test3 = window.cdc_;
                const test4 = window.$cdc_;
                const test5 = window.$chrome_asyncScriptInfo;

                if (test1 || test2 || test3 || test4 || test5) {
                    this.results.webdriver = true;
                    return;
                }

                // Check for missing properties real browsers have
                const testChrome = window.Chrome;
                if (testChrome && !testChrome.runtime) {
                    this.results.webdriver = true;
                }
            } catch (e) {
                // Ignore
            }
        }

        evaluate() {
            let score = 0;
            const reasons = [];

            // Canvas (30 points) - most important
            if (this.results.canvas) {
                score += 30;
            } else {
                reasons.push('no_canvas');
            }

            // WebGL (20 points)
            if (this.results.webgl) {
                score += 20;
            } else {
                reasons.push('no_webgl');
            }

            // Audio (15 points)
            if (this.results.audio) {
                score += 15;
            } else {
                reasons.push('no_audio');
            }

            // Timezone (15 points)
            if (this.results.timezone) {
                score += 15;
            } else {
                reasons.push('tz_mismatch');
            }

            // Plugins (10 points)
            if (this.results.hasPlugins) {
                score += 10;
            } else {
                reasons.push('no_plugins');
            }

            // Languages (5 points)
            if (this.results.languagesValid) {
                score += 5;
            } else {
                reasons.push('lang_mismatch');
            }

            // Screen (5 points)
            if (this.results.screenMatch) {
                score += 5;
            } else {
                reasons.push('screen_mismatch');
            }

            // WebDriver detected
            if (this.results.webdriver) {
                score = 0;
                reasons.unshift('webdriver_detected');
            }

            this.results.score = score;

            // Bot threshold: score < 60
            if (score < 60) {
                this.results.isBot = true;
                this.results.reason = reasons[0] || 'low_fingerprint_score';
            } else {
                this.results.isBot = false;
            }

            // Timeout protection
            setTimeout(() => {
                if (this.results.score === 0) {
                    this.results.isBot = true;
                    this.results.reason = 'timeout';
                }
            }, this.timeout);
        }

        hashString(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return Math.abs(hash).toString(16);
        }

        hashArray(arr) {
            let hash = 0;
            for (let i = 0; i < Math.min(arr.length, 100); i++) {
                hash = ((hash << 5) - hash) + Math.abs(Math.round(arr[i]));
                hash = hash & hash;
            }
            return Math.abs(hash).toString(16);
        }
    }

    // Export for global use
    window.BotDetector = BotDetector;

    // Auto-run if configuration is provided
    if (typeof window !== 'undefined' && !window.onload) {
        window.onload = function() {
            // Look for configuration in page or script tag
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i].getAttribute('src') || '';
                if (src.indexOf('cloak') !== -1 || src.indexOf('challenge') !== -1) {
                    // Auto-initialize with default config
                    const detector = new BotDetector({
                        timeout: 3000,
                        tests: ['canvas', 'webgl', 'audio', 'timezone'],
                        tzStart: -720, // UTC-12
                        tzEnd: 840,     // UTC+14
                        callback: function(result) {
                            // Send results to server
                            fetch('/js/challenge', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(result)
                            }).then(response => {
                                if (response.redirected) {
                                    window.location.href = response.url;
                                } else {
                                    return response.json().catch(() => {
                                        return response.text().then(html => {
                                            document.body.innerHTML = html;
                                        });
                                    });
                                }
                            }).then(data => {
                                if (!data) return;
                                if (data.action === 'redirect') {
                                    window.location.href = data.url;
                                } else if (data.action === 'block') {
                                    window.location.href = '/404';
                                } else if (data.action === 'safe') {
                                    document.cookie = 'cloak_verified=' + data.token + '; path=/; max-age=86400';
                                }
                            }).catch(err => {
                                console.error('Cloak challenge error:', err);
                            });
                        }
                    });
                    detector.monitor();
                    break;
                }
            }
        };
    }
})();
