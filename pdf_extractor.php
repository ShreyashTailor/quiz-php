<?php
/**
 * Simple PDF Text Extractor
 * This is a basic implementation for demonstration purposes.
 * For production use, consider using libraries like TCPDF, FPDF, or pdf2text
 */

class SimplePDFExtractor {
    
    public static function extractText($pdfPath) {
        // This is a simplified approach
        // In production, you should use a proper PDF parsing library
        
        try {
            // Try to read basic text content from PDF
            $content = file_get_contents($pdfPath);
            
            // Simple text extraction using regex (very basic)
            // This won't work for all PDFs, but it's a starting point
            $text = '';
            
            // Look for text objects in PDF
            if (preg_match_all('/\(([^)]+)\)/', $content, $matches)) {
                $text = implode(' ', $matches[1]);
            }
            
            // If no text found using simple method, try alternative approach
            if (empty(trim($text))) {
                // Try to find stream objects
                if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches)) {
                    foreach ($matches[1] as $stream) {
                        // Basic text extraction from streams
                        $decoded = @gzuncompress($stream);
                        if ($decoded !== false) {
                            $text .= $decoded . ' ';
                        }
                    }
                }
            }
            
            // Clean up the extracted text
            $text = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            // If still no meaningful text, return a placeholder with filename
            if (empty($text) || strlen($text) < 50) {
                $filename = basename($pdfPath);
                $text = "This PDF document ($filename) contains content that requires advanced text extraction. ";
                $text .= "The document appears to contain educational material suitable for quiz generation. ";
                $text .= "Please create questions based on typical topics that might be covered in such a document.";
            }
            
            return $text;
            
        } catch (Exception $e) {
            // Return a generic message if extraction fails
            $filename = basename($pdfPath);
            return "Unable to extract text from PDF ($filename). Please create general quiz questions based on the document title and common educational topics.";
        }
    }
    
    /**
     * Alternative method using pdftotext if available
     * Requires pdftotext to be installed on the server
     */
    public static function extractTextWithPdfToText($pdfPath) {
        $command = "pdftotext '$pdfPath' -";
        $text = shell_exec($command);
        
        if ($text && strlen(trim($text)) > 50) {
            return trim($text);
        }
        
        // Fallback to simple extraction
        return self::extractText($pdfPath);
    }
    
    /**
     * Get PDF metadata
     */
    public static function getPDFInfo($pdfPath) {
        $info = [];
        $content = file_get_contents($pdfPath);
        
        // Extract title
        if (preg_match('/\/Title\s*\(([^)]+)\)/', $content, $matches)) {
            $info['title'] = $matches[1];
        }
        
        // Extract author
        if (preg_match('/\/Author\s*\(([^)]+)\)/', $content, $matches)) {
            $info['author'] = $matches[1];
        }
        
        // Extract subject
        if (preg_match('/\/Subject\s*\(([^)]+)\)/', $content, $matches)) {
            $info['subject'] = $matches[1];
        }
        
        $info['filesize'] = filesize($pdfPath);
        $info['filename'] = basename($pdfPath);
        
        return $info;
    }
}
?>
