"use client";

import React from 'react';
import { ArrowRight, ShieldCheck, Gauge, Zap, Database, Lock, Globe } from 'lucide-react';
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export default function LandingPage() {
  return (
    <div className="flex flex-col min-h-screen bg-slate-950 text-slate-50 selection:bg-emerald-500/30">
      <header className="px-6 lg:px-12 h-20 flex items-center border-b border-white/5 bg-slate-950/50 backdrop-blur-md sticky top-0 z-50">
        <div className="flex items-center gap-2 font-bold text-xl tracking-tight">
          <div className="w-8 h-8 rounded bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.3)]">
            <Zap className="w-5 h-5 text-slate-950 fill-current" />
          </div>
          <span>ZAI <span className="text-emerald-400">TDS</span></span>
        </div>
        <nav className="ml-auto flex gap-6 text-sm font-medium text-slate-400">
          <a href="#" className="hover:text-emerald-400 transition-colors">Documentation</a>
          <a href="#" className="hover:text-emerald-400 transition-colors">API Reference</a>
          <a href="#" className="hover:text-emerald-400 transition-colors">Status</a>
        </nav>
      </header>

      <main className="flex-1">
        <section className="py-24 px-6 lg:px-12 max-w-7xl mx-auto text-center">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold mb-8 animate-pulse">
             <div className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
             CORE INFRASTRUCTURE PRODUCTION-READY
          </div>
          <h1 className="text-5xl lg:text-7xl font-bold tracking-tight mb-6 bg-clip-text text-transparent bg-gradient-to-b from-white to-slate-400">
            Intelligent Traffic <br className="hidden md:block" />
            Distribution Infrastructure
          </h1>
          <p className="text-slate-400 text-lg max-w-2xl mx-auto mb-10 leading-relaxed">
            Modern high-performance TDS engine built with Next.js 16 and Prisma. 
            Behaviorally compatible with Keitaro PHP, hardened for the modern web.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <Button size="lg" className="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold px-8 shadow-[0_0_20px_rgba(16,185,129,0.2)]">
              Primary Administrative Hub
              <ArrowRight className="ml-2 w-4 h-4" />
            </Button>
            <Button size="lg" variant="outline" className="border-slate-800 hover:bg-slate-900 px-8">
              System Health
            </Button>
          </div>
        </section>

        <section className="py-20 px-6 lg:px-12 max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="bg-slate-900/50 border-white/5 backdrop-blur-sm group hover:border-emerald-500/30 transition-all transition-colors duration-300">
            <CardHeader>
              <Gauge className="w-10 h-10 text-emerald-400 mb-2 group-hover:scale-110 transition-transform duration-300" />
              <CardTitle className="text-white">Unified Pipeline</CardTitle>
              <CardDescription className="text-slate-400">
                Single-pass click processing engine replacing legacy bespoke logic across all endpoints.
              </CardDescription>
            </CardHeader>
          </Card>
          <Card className="bg-slate-900/50 border-white/5 backdrop-blur-sm group hover:border-emerald-500/30 transition-all transition-colors duration-300">
            <CardHeader>
              <ShieldCheck className="w-10 h-10 text-emerald-400 mb-2 group-hover:scale-110 transition-transform duration-300" />
              <CardTitle className="text-white">Hardened Auth</CardTitle>
              <CardDescription className="text-slate-400">
                SHA256 hashed session tracking and header-only API authentication pathways.
              </CardDescription>
            </CardHeader>
          </Card>
          <Card className="bg-slate-900/50 border-white/5 backdrop-blur-sm group hover:border-emerald-500/30 transition-all transition-colors duration-300">
            <CardHeader>
              <Lock className="w-10 h-10 text-emerald-400 mb-2 group-hover:scale-110 transition-transform duration-300" />
              <CardTitle className="text-white">Zod Validation</CardTitle>
              <CardDescription className="text-slate-400">
                Strict schema enforcement for all administrative mutations ensuring data integrity.
              </CardDescription>
            </CardHeader>
          </Card>
          <Card className="bg-slate-900/50 border-white/5 backdrop-blur-sm group hover:border-emerald-500/30 transition-all transition-colors duration-300">
            <CardHeader>
              <Globe className="w-10 h-10 text-emerald-400 mb-2 group-hover:scale-110 transition-transform duration-300" />
              <CardTitle className="text-white">Geo/Bot Precision</CardTitle>
              <CardDescription className="text-slate-400">
                Production-grade GeoIP2 and async bot detection wired directly into the click flow.
              </CardDescription>
            </CardHeader>
          </Card>
        </section>

        <section className="py-20 px-6 lg:px-12 max-w-7xl mx-auto">
          <div className="bg-gradient-to-br from-emerald-500/10 via-slate-900 to-slate-950 border border-white/5 p-8 lg:p-12 rounded-3xl overflow-hidden relative group">
            <div className="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 blur-[100px] -mr-32 -mt-32" />
            <div className="grid lg:grid-cols-2 gap-12 items-center relative z-10">
              <div>
                <h2 className="text-3xl lg:text-4xl font-bold mb-6 text-white">Full Behavioral Parity with Keitaro PHP</h2>
                <ul className="space-y-4">
                  {[
                    "Weight-based stream selection with recursive retry",
                    "Complete macro coverage (SubIDs, ClickIDs, ExternalIDs)",
                    "13-stage Second Level Pipeline for Landing-to-Offer flows",
                    "Recursive campaign-redirect loop protection",
                    "Standardized JSON API responses with error codes"
                  ].map((item, i) => (
                    <li key={i} className="flex items-center gap-3 text-slate-300">
                      <div className="w-5 h-5 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center shrink-0">
                        <Zap className="w-3 h-3 text-emerald-400 fill-current" />
                      </div>
                      {item}
                    </li>
                  ))}
                </ul>
              </div>
              <div className="bg-slate-950/80 border border-white/10 rounded-2xl p-6 font-mono text-sm shadow-2xl">
                <div className="flex gap-2 mb-4">
                  <div className="w-3 h-3 rounded-full bg-red-500/50" />
                  <div className="w-3 h-3 rounded-full bg-amber-500/50" />
                  <div className="w-3 h-3 rounded-full bg-emerald-500/50" />
                </div>
                <div className="space-y-2 text-slate-400">
                  <p className="text-emerald-400"># System Boot Successful</p>
                  <p>[INFO] Initializing TDS Engine v1.0.0...</p>
                  <p>[INFO] GeoDB: Loaded MaxMind City/ASN</p>
                  <p>[INFO] BotDetection: Loaded 118 patterns</p>
                  <p>[INFO] Pipeline: 25 stages registered</p>
                  <p>[INFO] Auth: SHA256 session vault active</p>
                  <p className="text-blue-400 mt-4 flex items-center gap-2">
                    <span className="animate-pulse">_</span> 
                    Listening on port 3000
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>

      <footer className="mt-auto px-6 py-10 border-t border-white/5 bg-slate-950/50 text-slate-500 text-sm">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
          <div className="flex items-center gap-2 opacity-50 grayscale hover:grayscale-0 transition-all duration-300">
            <Zap className="w-4 h-4" />
            <span className="font-bold">ZAI TDS v1.0.0</span>
          </div>
          <p>© 2026 Advanced Agentic Coding. Built for Modern Traffic Operations.</p>
          <div className="flex gap-6">
            <span className="inline-flex items-center gap-1.5 ">
              <div className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
              Operational
            </span>
          </div>
        </div>
      </footer>
    </div>
  );
}
