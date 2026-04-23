import React from "react";
import { NavigationProvider } from "./providers/NavigationProvider";

export default function RootLayout({ children }) {
  return (
    <NavigationProvider>
      {children}
    </NavigationProvider>
  );
} 