<style>
    .template{
        container-name: "template";
        container-type: inline-size;
    }
    @container template (max-width: 500px) {
        
    }
</style>

<div class="template">
    <slot></slot>
</div>