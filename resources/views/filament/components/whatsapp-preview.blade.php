<div style="background: #1f2937; border-radius: 12px; padding: 20px; max-width: 320px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
        <div style="width: 50px; height: 50px; background: #25D366; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <span style="color: white; font-size: 24px; font-weight: bold;">W</span>
        </div>
        <div style="flex: 1;">
            <div style="color: white; font-weight: 600; font-size: 16px;">{{ $agentName }}</div>
            <div style="color: #9ca3af; font-size: 13px;">{{ $agentTitle }}</div>
        </div>
        <div style="color: #6b7280; font-size: 20px;">×</div>
    </div>
    
    <div style="background: #374151; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
        <p style="color: #d1d5db; margin: 0; font-size: 14px; line-height: 1.5; white-space: pre-line;">{{ $greetingMessage }}</p>
    </div>
    
    <button style="width: 100%; background: #25D366; color: white; border: none; border-radius: 8px; padding: 14px; font-size: 16px; font-weight: 600; cursor: pointer;">
        Start Chat
    </button>
</div>
