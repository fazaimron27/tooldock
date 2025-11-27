/**
 * Application footer component displaying copyright information
 */
export default function Footer() {
  return (
    <footer className="shrink-0 border-t bg-background px-4 py-3 text-sm text-muted-foreground">
      <div className="container mx-auto text-center">
        <p>&copy; {new Date().getFullYear()} Mosaic. All rights reserved.</p>
      </div>
    </footer>
  );
}
