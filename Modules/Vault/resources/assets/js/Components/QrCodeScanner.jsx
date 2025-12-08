/**
 * QR Code Scanner component for TOTP setup
 * Scans QR codes containing otpauth:// URLs and extracts the secret
 * Uses @yudiel/react-qr-scanner for camera scanning and browser BarcodeDetector API for file scanning
 */
import { cn } from '@/Utils/utils';
import { Scanner } from '@yudiel/react-qr-scanner';
import { Camera, Image, Loader2 } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/Components/ui/button';

export default function QrCodeScanner({ onScan, onClose }) {
  const [scanning, setScanning] = useState(false);
  const [mode, setMode] = useState('file'); // 'file' or 'camera'
  const [error, setError] = useState(null);
  const [paused, setPaused] = useState(false);
  const fileInputRef = useRef(null);

  /**
   * Parse otpauth:// URL and extract all available information
   * Format: otpauth://totp/Label?secret=SECRET&issuer=ISSUER&algorithm=SHA1&digits=6&period=30
   * Supports: otpauth://totp/huhu:blabalba%40gmail.com?secret=xxx&issuer=xxx
   *
   * Returns object with: { secret, name, issuer, username, email, algorithm, digits, period }
   */
  const parseOtpauthUrl = useCallback((url) => {
    try {
      // Decode URL if it's encoded
      let decodedUrl = url;
      try {
        decodedUrl = decodeURIComponent(url);
      } catch {
        // If decoding fails, use original URL
        decodedUrl = url;
      }

      // Parse otpauth:// URL manually since URL constructor doesn't handle custom protocols well
      if (!decodedUrl.startsWith('otpauth://')) {
        throw new Error('Invalid QR code format - must start with otpauth://');
      }

      // Extract path and query string
      const protocolEnd = decodedUrl.indexOf('://') + 3;
      const pathStart = decodedUrl.indexOf('/', protocolEnd);
      const queryIndex = decodedUrl.indexOf('?');

      if (queryIndex === -1) {
        throw new Error('No query parameters found in QR code');
      }

      // Extract label from path (e.g., "totp/huhu:blabalba@gmail.com")
      let label = '';
      if (pathStart !== -1 && pathStart < queryIndex) {
        const pathPart = decodedUrl.substring(pathStart + 1, queryIndex);
        // Remove "totp/" prefix if present
        label = pathPart.replace(/^totp\//, '');
      }

      // Extract query parameters
      const queryString = decodedUrl.substring(queryIndex + 1);
      const params = new URLSearchParams(queryString);
      const secret = params.get('secret');
      const issuer = params.get('issuer') || '';
      const algorithm = params.get('algorithm') || 'SHA1';
      const digits = params.get('digits') || '6';
      const period = params.get('period') || '30';

      if (!secret) {
        throw new Error('No secret parameter found in QR code');
      }

      // Trim whitespace and validate secret is not empty
      const trimmedSecret = secret.trim();
      if (!trimmedSecret) {
        throw new Error('Secret parameter is empty');
      }

      // Parse label to extract username/email
      // Label format can be: "username", "issuer:username", "username:email", etc.
      let username = '';
      let email = '';
      let name = '';

      if (label) {
        // Try to parse label - common formats:
        // 1. "username:email" (e.g., "huhu:blabalba@gmail.com")
        // 2. "issuer:username" (e.g., "Google:user@gmail.com")
        // 3. Just "username" or "email"
        const colonIndex = label.indexOf(':');

        if (colonIndex !== -1) {
          const part1 = label.substring(0, colonIndex).trim();
          const part2 = label.substring(colonIndex + 1).trim();

          // Check if part2 looks like an email
          if (part2.includes('@')) {
            email = part2;
            username = part1;
            name = issuer || part1;
          } else {
            // Could be issuer:username format
            username = part2;
            name = issuer || part1;
          }
        } else {
          // No colon, check if it's an email or username
          if (label.includes('@')) {
            email = label;
            name = issuer || email.split('@')[0];
          } else {
            username = label;
            name = issuer || label;
          }
        }
      } else {
        // No label, use issuer as name
        name = issuer || 'TOTP Account';
      }

      return {
        secret: trimmedSecret,
        name: name.trim() || 'TOTP Account',
        issuer: issuer.trim() || '',
        username: username.trim() || '',
        email: email.trim() || '',
        algorithm: algorithm.trim(),
        digits: digits.trim(),
        period: period.trim(),
      };
    } catch (err) {
      throw new Error(`Failed to parse QR code: ${err.message}`);
    }
  }, []);

  /**
   * Scan QR code from image file
   */
  const handleFileScan = useCallback(
    async (file) => {
      if (!file) {
        return;
      }

      setScanning(true);
      setError(null);

      try {
        // Use browser's native BarcodeDetector API for file scanning
        if (!('BarcodeDetector' in window)) {
          throw new Error(
            'BarcodeDetector API is not supported in this browser. Please use the camera option instead.'
          );
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
          throw new Error('Please select a valid image file.');
        }

        // Create image from file
        const imageUrl = window.URL.createObjectURL(file);
        const img = document.createElement('img');
        await new Promise((resolve, reject) => {
          img.onload = resolve;
          img.onerror = reject;
          img.src = imageUrl;
        });

        // Use BarcodeDetector to scan the image
        // BarcodeDetector is a browser API, available in modern browsers
        const barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
        const barcodes = await barcodeDetector.detect(img);

        // Clean up object URL to prevent memory leaks
        window.URL.revokeObjectURL(imageUrl);

        if (!barcodes || barcodes.length === 0) {
          throw new Error(
            'No QR code found in the image. Please ensure the image contains a clear QR code.'
          );
        }

        const result = barcodes[0].rawValue;

        // Check if result is valid
        if (!result || typeof result !== 'string') {
          throw new Error('Invalid QR code format - no text found in QR code');
        }

        // Check if it's a TOTP QR code
        if (!result.trim().startsWith('otpauth://')) {
          throw new Error(
            `QR code does not contain a TOTP secret. Found: ${result.substring(0, 50)}...`
          );
        }

        const parsedData = parseOtpauthUrl(result);
        onScan(parsedData);
        toast.success('QR code scanned successfully');
        onClose();
      } catch (err) {
        let errorMessage = 'Failed to scan QR code.';

        if (err.message) {
          if (err.message.includes('No QR code found')) {
            errorMessage =
              'No QR code found in the image. Please ensure the image contains a clear QR code.';
          } else if (err.message.includes('does not contain a TOTP secret')) {
            errorMessage = err.message;
          } else if (err.message.includes('Invalid QR code format')) {
            errorMessage = err.message;
          } else if (err.message.includes('Failed to parse QR code')) {
            errorMessage = err.message;
          } else {
            errorMessage = `Failed to scan QR code: ${err.message}`;
          }
        } else {
          errorMessage = 'Failed to scan QR code. Please ensure it contains a valid TOTP QR code.';
        }

        setError(errorMessage);
        toast.error('Failed to scan QR code', {
          description: errorMessage,
        });
      } finally {
        setScanning(false);
      }
    },
    [onScan, onClose, parseOtpauthUrl]
  );

  const handleFileInput = useCallback(
    (e) => {
      const file = e.target.files?.[0];
      if (file) {
        handleFileScan(file);
      }
      // Reset input to allow selecting the same file again
      e.target.value = '';
    },
    [handleFileScan]
  );

  const handleScan = useCallback(
    (detectedCodes) => {
      if (detectedCodes && detectedCodes.length > 0) {
        const code = detectedCodes[0];
        const decodedText = code.rawValue;

        try {
          const parsedData = parseOtpauthUrl(decodedText);

          // Pause scanning
          setPaused(true);
          setScanning(false);

          onScan(parsedData);
          toast.success('QR code scanned successfully');
          onClose();
        } catch {
          // Continue scanning - don't show error toast for parse errors during scanning
          // Invalid QR codes will be silently ignored to allow continuous scanning
        }
      }
    },
    [onScan, onClose, parseOtpauthUrl]
  );

  const handleError = useCallback((err) => {
    let errorMessage = 'Failed to start camera.';
    if (err && typeof err === 'object') {
      if (err.message) {
        if (err.message.includes('permission') || err.message.includes('Permission')) {
          errorMessage =
            'Camera permission denied. Please allow camera access in your browser settings.';
        } else if (err.message.includes('not found') || err.message.includes('No cameras')) {
          errorMessage = 'No camera found on this device.';
        } else {
          errorMessage = `Failed to start camera: ${err.message}`;
        }
      }
    }
    setError(errorMessage);
    setScanning(false);
    toast.error('Camera access failed', {
      description: errorMessage,
    });
  }, []);

  return (
    <div className="space-y-4 p-4">
      <div>
        <h3 className="text-lg font-semibold">Scan QR Code</h3>
      </div>

      {/* Mode Toggle */}
      <div className="flex gap-2">
        <Button
          type="button"
          variant={mode === 'file' ? 'default' : 'outline'}
          size="sm"
          onClick={() => {
            setMode('file');
            setError(null);
            setPaused(true);
            setScanning(false);
          }}
          disabled={scanning && mode === 'camera'}
        >
          <Image className="h-4 w-4 mr-2" />
          Upload Image
        </Button>
        <Button
          type="button"
          variant={mode === 'camera' ? 'default' : 'outline'}
          size="sm"
          onClick={() => {
            setMode('camera');
            setError(null);
            setPaused(false);
            setScanning(true);
          }}
          disabled={scanning && mode === 'file'}
        >
          <Camera className="h-4 w-4 mr-2" />
          Use Camera
        </Button>
      </div>

      {/* Scanner Area */}
      <div className="relative">
        <div
          className={cn(
            'w-full min-h-[300px] rounded-lg border-2 border-dashed flex items-center justify-center overflow-hidden',
            scanning && mode === 'camera' ? 'bg-black' : 'bg-muted/50',
            error && 'border-destructive'
          )}
        >
          {mode === 'camera' && (
            <div className="w-full h-full">
              <Scanner
                onScan={handleScan}
                onError={handleError}
                paused={paused}
                constraints={{ facingMode: 'environment' }}
                formats={['qr_code']}
                scanDelay={300}
                styles={{
                  container: { width: '100%', height: '100%' },
                  video: { width: '100%', height: '100%', objectFit: 'cover' },
                }}
              />
            </div>
          )}

          {!scanning && mode === 'file' && (
            <div className="flex flex-col items-center gap-4 p-8">
              <Image className="h-12 w-12 text-muted-foreground" />
              <div className="text-center">
                <p className="text-sm font-medium">Upload QR Code Image</p>
                <p className="text-xs text-muted-foreground mt-1">
                  Select an image containing the TOTP QR code
                </p>
              </div>
              <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                onChange={handleFileInput}
                className="hidden"
                id="qr-file-input"
              />
              <Button type="button" variant="outline" onClick={() => fileInputRef.current?.click()}>
                Choose Image
              </Button>
            </div>
          )}

          {scanning && mode === 'file' && (
            <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 text-white z-10 pointer-events-none">
              <Loader2 className="h-8 w-8 animate-spin" />
              <p className="text-sm">Scanning image...</p>
            </div>
          )}
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 p-3">
          <p className="text-sm text-destructive">{error}</p>
        </div>
      )}
    </div>
  );
}
