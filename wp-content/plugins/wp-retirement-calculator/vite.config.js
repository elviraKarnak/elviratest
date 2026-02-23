import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { resolve } from "path";

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: "dist",
    emptyOutDir: true,
    rollupOptions: {
      input: resolve(__dirname, "assets/frontend.jsx"),
      output: {
        entryFileNames: "index.js",
        assetFileNames: "style.css",
      },
    },
  },
});
