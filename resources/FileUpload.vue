<template>
    <v-container fluid class="pa-0">
        <v-layout row wrap>
            <v-flex sm12>
                <h3 v-if="label" class="subheading">{{ label }}</h3>
            </v-flex>
            <v-flex v-for="(image, index) in images" :key="index" sm6 md3>
                <v-card>
                    <img :src="image.src">
                    <v-card-title>
                        <div class="subheading">{{ image.filename }}</div>
                    </v-card-title>
                    <v-card-title>
                        <div class="caption">{{ size(image.size) }}</div>
                    </v-card-title>
                </v-card>
            </v-flex>
            <v-flex sm12>
                <v-btn @click="openDialog()"
                    color="primary"
                    class="ma-0"
                    small
                    dark
                >
                    Datei auswählen…
                </v-btn>
                <input type="file" ref="uploader" @change="pushToQueue()" style="display: none;" :multiple="multiple" />
            </v-flex>
        </v-layout>
    </v-container>
</template>

<style lang="scss" scoped>
    input[type="file"] {
        display: none;
    }

    img {
        max-width: 100%;
    }
</style>

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
                    return {image: null};
                }
            }
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

                if (this.multiple) {
                    this.value.images.push[{
                        filename: file.name,
                        size: file.size,
                        src: null
                    }];
                } else {
                    this.$set(this.value, 'image', {
                        filename: file.name,
                        size: file.size,
                        src: null
                    });
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    vm.value.image.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        computed: {
            images() {
                if (typeof this.value.image == "undefined" && typeof this.value.images == "undefined") {
                    return [];
                }

                if (this.multiple) {
                    return this.value.images || [];
                }

                return this.value.image ? [this.value.image] : [];
            }
        }
    }

</script>
