import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { Toaster } from "@/components/ui/toaster";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "TDS - Traffic Distribution System",
  description: "Keitaro-style Traffic Distribution System with cloaking, bot detection, and affiliate tracking. Reverse-engineered from yljary.com infrastructure.",
  keywords: ["TDS", "Traffic Distribution", "Keitaro", "Cloaking", "Affiliate", "Bot Detection"],
  authors: [{ name: "TDS Platform" }],
  icons: {
    icon: "/logo.svg",
  },
  openGraph: {
    title: "Traffic Distribution System",
    description: "Professional traffic distribution and cloaking platform",
    type: "website",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased bg-background text-foreground`}
      >
        {children}
        <Toaster />
      </body>
    </html>
  );
}
