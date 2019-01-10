<template>
    <div>
        <div>
            <div class="tw-flex-nogrow tw-text-lg tw-text-grey-darker tw-mb-1 tw-font-narrow tw-uppercase">
                <slot></slot>
            </div>
        </div>

        <div class="tw-flex tw-flex-wrap">
            <div v-for="(image, index) in this.value" :key="index" class="tw-w-1/5">
                <img :src="image.src">

                <footer>
                    <span>{{ image.filename }}</span>
                    <span>{{ size(image.size) }}</span>
                </footer>
            </div>

            <div class="tw-flex-grow tw-w-full tw-mt-2">
                <button type="button" class="
                        tw-px-3 tw-py-2
                        tw-bg-primary-light hover:tw-bg-primary-lighter
                        tw-flex-nogrow
                        tw-text-white tw-uppercase tw-no-underline hover:tw-bg-primary-light
                        tw-inline-block
                    "
                    @click="openDialog()"
                >Datei auswählen…</button>
            </div>

            <input type="file" ref="uploader" @change="pushToQueue()" :multiple="multiple" class="tw-hidden" />
            <canvas ref="resizer" v-if="resize" :width="resizeWidth" :height="resizeHeight"></canvas>
        </div>
    </div>
</template>

<script>

    export default {
        props: {
            multiple: {
                default: false
            },
            label: {
                default: ''
            },
            value: {
                default: function() {
                    return null;
                }
            },
            resize: {
                default: null
            }
        },
        data: function() {
            return {
                images: []
            };
        },
        methods: {
            openDialog() {
                this.$refs.uploader.click();
            },
            pushToQueue() {
                var files = this.$refs.uploader.files;
                for (var i = 0; i < files.length; i++) {
                    this.upload(files[i]);
                }
            },
            size(s) {
                return (s / 100) + 'KB';
            },
            upload(file) {
                var vm = this;

                var reader = new FileReader();

                var model = {
                    filename: file.name,
                    size: file.size,
                    src: null
                };

                reader.onload = () => {

                    if (this.resize) {
                        var resizer = this.$refs.resizer.getContext('2d');

                        var bigImage = new Image();
                        bigImage.src = reader.result;

                        bigImage.onload = () => {
                            var svhSrc = bigImage.width / bigImage.height;
                            var svhDest = this.resizeWidth / this.resizeHeight;

                            if (svhDest < svhSrc) {
                                var h = bigImage.height;
                                var w = svhDest * bigImage.height;
                                var x = (bigImage.width - w) / 2;
                                var y = 0;
                            } else {
                                w = bigImage.width;
                                h = bigImage.width / svhDest;
                                y = (bigImage.height - h) / 2;
                                x = 0;
                            }

                            resizer.drawImage(bigImage,
                                x, y,
                                w, h,
                                0, 0,
                                this.resizeWidth, this.resizeHeight
                            );
                            model.src = this.$refs.resizer.toDataURL();
                        };
                    } else {
                        model.src = reader.result;

                        var formData = new FormData();
                        formData.append('file', file);
                        var req = new XMLHttpRequest();
                        req.open('POST', '/api/fileupload', true);
                        req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        req.setRequestHeader('X-CSRF-TOKEN', document.head.querySelector('meta[name="csrf-token"]').content);

                        req.onload = () => {
                            if (req.status != 200) {return;}

                            model.tempName = JSON.parse(req.response).filename;
                            this.value.push(model);
                            this.$emit('input', this.value);
                        };

                        req.send(formData);
                    }
                };

                reader.readAsDataURL(file);
            }
        },
        computed: {
            resizeWidth() {
                if (!this.resize) {
                    return 0;
                }

                return this.resize[0];
            },
            resizeHeight() {
                if (!this.resize) {
                    return 0;
                }

                return this.resize[1];
            }
        }
    }

</script>
