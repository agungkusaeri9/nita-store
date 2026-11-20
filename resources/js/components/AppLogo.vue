<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage<any>();
</script>

<template>
    <img
        src="/images/logo.png"
        :alt="page.props.name || 'Logo'"
        class="h-20 w-full max-w-[180px] shrink-0 object-contain group-data-[collapsible=icon]:h-8 group-data-[collapsible=icon]:w-8"
    />
</template>
