<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Account Settings" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Page header -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-5">
                <div class="max-w-2xl mx-auto">
                    <h1 class="text-lg font-black text-[#1A1A1A]">Account Settings</h1>
                    <p class="text-xs text-[#555555] mt-0.5">Manage your profile and security preferences</p>
                </div>
            </div>

            <div class="max-w-2xl mx-auto px-4 md:px-8 py-8 space-y-5">
                <div class="bg-white rounded-2xl border border-[#DDDDDD] p-6">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                </div>

                <div class="bg-white rounded-2xl border border-[#DDDDDD] p-6">
                    <UpdatePasswordForm />
                </div>

                <div class="bg-white rounded-2xl border border-[#DDDDDD] p-6">
                    <DeleteUserForm />
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
