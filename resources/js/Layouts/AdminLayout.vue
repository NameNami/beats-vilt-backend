<template>
    <div class="flex h-screen bg-[#f4f9f9] font-sans text-slate-800">

        <aside class="w-64 bg-[#e8f6f5] border-r border-[#d1ebe8] flex flex-col justify-between hidden md:flex">
            <div>
                <div class="p-6 flex items-center gap-3">
                    <div class="w-8 h-8 bg-amber-700 rounded-md flex items-center justify-center text-white font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-slate-900">BEATS</h1>
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">School Administration</p>
                    </div>
                </div>

                <nav class="mt-4 space-y-1">
                    <Link href="/admin/dashboard" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url === '/admin/dashboard', 'text-slate-600 hover:bg-[#dff0ef]': $page.url !== '/admin/dashboard'}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </Link>

                    <div class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Core Directory</div>

                    <Link href="/admin/users" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url.startsWith('/admin/users'), 'text-slate-600 hover:bg-[#dff0ef]': !$page.url.startsWith('/admin/users')}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        User Accounts
                    </Link>
                    <Link href="/admin/students" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url.startsWith('/admin/students'), 'text-slate-600 hover:bg-[#dff0ef]': !$page.url.startsWith('/admin/students')}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                        Students
                    </Link>
                    <Link href="/admin/lecturers" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url.startsWith('/admin/lecturers'), 'text-slate-600 hover:bg-[#dff0ef]': !$page.url.startsWith('/admin/lecturers')}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Lecturers
                    </Link>

                    <div class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Academic Ops</div>

                    <Link href="/admin/courses" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url.startsWith('/admin/courses'), 'text-slate-600 hover:bg-[#dff0ef]': !$page.url.startsWith('/admin/courses')}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        Courses & Labs
                    </Link>
                    <Link href="/admin/sessions" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url.startsWith('/admin/sessions'), 'text-slate-600 hover:bg-[#dff0ef]': !$page.url.startsWith('/admin/sessions')}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Class Sessions
                    </Link>
                    <Link href="/admin/analytics" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url.startsWith('/admin/analytics'), 'text-slate-600 hover:bg-[#dff0ef]': !$page.url.startsWith('/admin/analytics')}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Global Analytics
                    </Link>

                    <div class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Configuration</div>

                    <Link href="/admin/settings" :class="{'bg-white text-amber-800 border-r-4 border-amber-700': $page.url.startsWith('/admin/settings'), 'text-slate-600 hover:bg-[#dff0ef]': !$page.url.startsWith('/admin/settings')}" class="flex items-center gap-3 px-6 py-3 font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        System Settings
                    </Link>
                </nav>
            </div>

            <div class="mb-6 space-y-1">
                <Link href="/logout" method="post" as="button" class="w-full flex items-center gap-3 px-6 py-3 text-slate-600 hover:bg-[#dff0ef] font-medium transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </Link>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">

            <header class="h-20 flex items-center justify-end px-8 bg-[#f4f9f9] border-b border-gray-100">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3 pl-6">
                        <div class="text-right">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ $page.props.auth?.user?.name || 'Admin Panel' }}</p>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=Admin&background=1e293b&color=fff" class="w-10 h-10 rounded-full border-2 border-white shadow-sm">
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
